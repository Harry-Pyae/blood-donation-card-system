<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class HospitalManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $hospitals = Hospital::query()
            ->with('users')
            ->withCount('bloodRequests')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('code', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%')
                        ->orWhere('region', 'like', '%'.$search.'%')
                        ->orWhere('address', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhereHas('users', function ($users) use ($search): void {
                            $users->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%')
                                ->orWhere('phone', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.national.hospitals', compact('hospitals', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(backpack_user()?->isSystemAdministrator(), 403);
        $data=$request->validate([
            'name'=>['required','string','max:180'],
            'region'=>['required','string',Rule::in(array_keys(Hospital::REGION_PREFIXES))],'address'=>['nullable','string','max:255'],'phone'=>['nullable','string','max:30'],
            'contact_name'=>['required','string','max:150'],'email'=>['required','email','unique:users,email'],'password'=>['required',Password::min(8)],
        ]);
        DB::transaction(function() use($data): void {
            $hospital=Hospital::create(['code'=>Hospital::generateCode($data['region']),'name'=>$data['name'],'region'=>$data['region'],'address'=>$data['address']??null,'phone'=>$data['phone']??null,'is_active'=>true]);
            $user=User::create(['name'=>$data['contact_name'],'email'=>strtolower($data['email']),'password'=>Hash::make($data['password']),'role'=>User::ROLE_HOSPITAL,'hospital_id'=>$hospital->id,'workplace'=>$hospital->name,'approval_status'=>User::APPROVAL_APPROVED,'approved_at'=>now(),'approved_by'=>backpack_user()?->id,'is_banned'=>false]);
            ActivityLog::record(['type'=>'Hospital','action'=>'Hospital portal account created','subject_type'=>Hospital::class,'subject_id'=>$hospital->id,'user_id'=>backpack_user()?->id,'result'=>'Active','details'=>"{$hospital->code}; {$hospital->name}; {$user->email}",'source'=>'admin-hospitals']);
        });
        return back()->with('status', __('bloodcare.national.hospitals.saved'));
    }

    public function update(Request $request, Hospital $hospital): RedirectResponse
    {
        abort_unless(backpack_user()?->isSystemAdministrator(), 403);
        $data = $request->validate([
            'name' => ['required','string','max:180'],
            'region' => ['required','string',Rule::in(array_keys(Hospital::REGION_PREFIXES))],
            'address' => ['nullable','string','max:255'],
            'phone' => ['nullable','string','max:30'],
            'is_active' => ['required','boolean'],
        ]);

        DB::transaction(function () use ($hospital, $data): void {
            $oldCode = $hospital->code;
            $code = $hospital->region === $data['region']
                ? $hospital->code
                : Hospital::generateCode($data['region']);

            $hospital->update([
                'code' => $code,
                'name' => $data['name'],
                'region' => $data['region'],
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'is_active' => (bool) $data['is_active'],
            ]);
            $hospital->users()->update(['workplace' => $hospital->name]);

            ActivityLog::record(['type'=>'Hospital','action'=>'Hospital details updated','subject_type'=>Hospital::class,'subject_id'=>$hospital->id,'user_id'=>backpack_user()?->id,'result'=>($hospital->is_active?'Active':'Inactive'),'details'=>"{$oldCode} -> {$hospital->code}; {$hospital->name}; {$hospital->region}",'source'=>'admin-hospitals']);
        });

        return back()->with('status', __('bloodcare.national.hospitals.updated'));
    }

    public function destroy(Hospital $hospital): RedirectResponse
    {
        abort_unless(backpack_user()?->isSystemAdministrator(), 403);

        if ($hospital->bloodRequests()->exists()) {
            return back()->withErrors(['hospital' => __('bloodcare.national.hospitals.delete_blocked')]);
        }

        DB::transaction(function () use ($hospital): void {
            $details = "{$hospital->code}; {$hospital->name}";
            $hospital->users()->update(['hospital_id' => null, 'is_banned' => true]);
            ActivityLog::record(['type'=>'Hospital','action'=>'Unused hospital account deleted','subject_type'=>Hospital::class,'subject_id'=>$hospital->id,'user_id'=>backpack_user()?->id,'result'=>'Deleted','details'=>$details,'source'=>'admin-hospitals']);
            $hospital->delete();
        });

        return back()->with('status', __('bloodcare.national.hospitals.deleted'));
    }
}
