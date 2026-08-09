<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BloodAllocation;
use App\Models\BloodRequest;
use App\Models\BloodUnit;
use App\Services\BloodCareNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BloodRequestManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $componentSearch = str_replace(' ', '_', strtolower($search));
        $requests=BloodRequest::query()
            ->with(['hospital','requester','allocations.unit'])
            ->when($search !== '', function ($query) use ($search, $componentSearch): void {
                $query->where(function ($nested) use ($search, $componentSearch): void {
                    $nested->where('reference', 'like', '%'.$search.'%')
                        ->orWhere('patient_reference', 'like', '%'.$search.'%')
                        ->orWhere('blood_group', 'like', '%'.$search.'%')
                        ->orWhere('component_type', 'like', '%'.$componentSearch.'%')
                        ->orWhere('priority', 'like', '%'.$search.'%')
                        ->orWhere('status', 'like', '%'.$componentSearch.'%')
                        ->orWhere('clinical_note', 'like', '%'.$search.'%')
                        ->orWhere('decision_note', 'like', '%'.$search.'%')
                        ->orWhereHas('hospital', function ($hospital) use ($search): void {
                            $hospital->where('code', 'like', '%'.$search.'%')
                                ->orWhere('name', 'like', '%'.$search.'%')
                                ->orWhere('phone', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('requester', function ($requester) use ($search): void {
                            $requester->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%')
                                ->orWhere('phone', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();
        $safeUnits=BloodUnit::query()
            ->where('status','available')
            ->whereNotNull('released_at')
            ->whereDate('expires_at','>=',today())
            ->whereDoesntHave('allocations')
            ->orderBy('expires_at')
            ->orderBy('collected_at')
            ->orderBy('unit_number')
            ->get();
        return view('admin.national.requests',compact('requests','safeUnits','search'));
    }

    public function review(Request $request, BloodRequest $bloodRequest, BloodCareNotificationService $notifications): RedirectResponse
    {
        $data=$request->validate(['decision'=>['required',Rule::in(['approve','reject'])],'reason'=>[$request->input('decision')==='reject'?'required':'nullable','string','max:2000']]);
        if(!in_array($bloodRequest->status,['pending','approved'],true)) throw ValidationException::withMessages(['decision'=>'Only pending requests can be reviewed.']);
        $bloodRequest->update(['status'=>$data['decision']==='approve'?'approved':'rejected','decision_note'=>$data['reason']??null,'reviewed_by'=>backpack_user()?->id,'reviewed_at'=>now()]);
        $this->log($bloodRequest,'Blood request reviewed',ucfirst($bloodRequest->status));
        $notifications->hospitalRequestReviewed($bloodRequest->fresh('requester'));
        return back()->with('status', __('bloodcare.national.requests.decision_saved'));
    }

    public function allocate(Request $request, BloodRequest $bloodRequest, BloodCareNotificationService $notifications): RedirectResponse
    {
        $data=$request->validate(['unit_number'=>['required','string'],'crossmatch_result'=>['required',Rule::in(['compatible','incompatible'])],'notes'=>['nullable','string','max:2000']]);
        DB::transaction(function() use($bloodRequest,$data,$notifications): void {
            $req=BloodRequest::query()->lockForUpdate()->findOrFail($bloodRequest->id);
            if(!in_array($req->status,['approved','partially_allocated'],true)) throw ValidationException::withMessages(['unit_number'=>'The request must be approved before allocation.']);
            if($data['crossmatch_result']!=='compatible') throw ValidationException::withMessages(['crossmatch_result'=>'An incompatible unit cannot be allocated.']);
            if($req->allocations()->whereNotIn('status',['cancelled'])->count()>=$req->quantity) throw ValidationException::withMessages(['unit_number'=>'The requested quantity is already fully allocated.']);
            $unit=BloodUnit::query()->where('unit_number',$data['unit_number'])->lockForUpdate()->firstOrFail();
            $modifierMismatch = ($req->requires_leukoreduced && ! $unit->leukoreduced)
                || ($req->requires_irradiated && ! $unit->irradiated)
                || ($req->requires_washed && ! $unit->washed);
            if($unit->status!=='available'||!$unit->released_at||$unit->expires_at->isPast()||$unit->blood_group!==$req->blood_group||$unit->component_type!==$req->component_type||$modifierMismatch||$unit->allocations()->exists()) {
                throw ValidationException::withMessages(['unit_number'=>'Unit is not safe and available for this request.']);
            }
            BloodAllocation::create(['blood_request_id'=>$req->id,'blood_unit_id'=>$unit->id,'crossmatch_result'=>'compatible','status'=>'allocated','allocated_by'=>backpack_user()?->id,'allocated_at'=>now(),'notes'=>$data['notes']??null]);
            $unit->update(['status'=>'reserved']);
            $count=$req->allocations()->count();
            $req->update(['status'=>$count>=$req->quantity?'allocated':'partially_allocated']);
            $this->log($req,'Safe blood unit allocated',"{$unit->unit_number}; cross-match compatible");
            $notifications->unitAllocated($req->loadMissing('requester'), $unit);
        });
        return back()->with('status', __('bloodcare.national.requests.allocated_saved'));
    }

    public function dispatch(Request $request, BloodAllocation $allocation, BloodCareNotificationService $notifications): RedirectResponse
    {
        $data=$request->validate(['reason'=>['required','string','max:1000']]);
        DB::transaction(function() use($allocation,$data,$notifications): void {
            $a=BloodAllocation::query()->with(['unit','request'])->lockForUpdate()->findOrFail($allocation->id);
            if($a->status!=='allocated'||$a->crossmatch_result!=='compatible'||$a->unit->status!=='reserved'||!$a->unit->released_at||$a->unit->expires_at->isPast()) throw ValidationException::withMessages(['reason'=>'Only a compatible, released, unexpired reserved unit can be dispatched.']);
            $a->update(['status'=>'dispatched','dispatched_at'=>now(),'notes'=>trim(($a->notes? $a->notes."\n":'').$data['reason'])]);
            $a->unit->update(['status'=>'used']);
            $remainingAllocated=$a->request->allocations()->where('status','allocated')->count();
            $a->request->update(['status'=>$remainingAllocated===0?'dispatched':'partially_dispatched']);
            $this->log($a->request,'Blood unit dispatched',"{$a->unit->unit_number}; {$data['reason']}");
            $notifications->unitDispatched($a->request->loadMissing('requester'), $a->unit);
        });
        return back()->with('status', __('bloodcare.national.requests.dispatch_saved'));
    }

    private function log(BloodRequest $request,string $action,string $details): void
    {
        ActivityLog::record(['type'=>'Hospital Request','action'=>$action,'subject_type'=>BloodRequest::class,'subject_id'=>$request->id,'user_id'=>backpack_user()?->id,'result'=>ucfirst(str_replace('_',' ',$request->status)),'details'=>"{$request->reference}; {$details}",'source'=>'admin-hospital-requests']);
    }
}
