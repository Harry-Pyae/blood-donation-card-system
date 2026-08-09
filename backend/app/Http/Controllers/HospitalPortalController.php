<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AdverseReaction;
use App\Models\BloodAllocation;
use App\Models\BloodRequest;
use App\Models\User;
use App\Services\BloodCareNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HospitalPortalController extends Controller
{
    public function loginForm(): View { return view('hospital.login'); }

    public function login(Request $request): RedirectResponse
    {
        $data=$request->validate(['email'=>['required','email'],'password'=>['required','string']]);
        $key=Str::lower($data['email']).'|'.$request->ip();
        if(RateLimiter::tooManyAttempts($key,5)) return back()->withErrors(['email'=>'Too many login attempts. Please try again shortly.'])->onlyInput('email');
        if(!Auth::attempt(['email'=>Str::lower($data['email']),'password'=>$data['password']],false)){RateLimiter::hit($key,60);return back()->withErrors(['email'=>'Invalid hospital credentials.'])->onlyInput('email');}
        $request->session()->regenerate();
        $user=Auth::user();
        if(!$user instanceof User||!$user->isHospitalUser()){Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();return back()->withErrors(['email'=>'This account is not an active hospital account.']);}
        RateLimiter::clear($key);
        if($user->hasTwoFactorEnabled()){
            Auth::logout();
            $request->session()->regenerate();
            $request->session()->put('two_factor.login',['user_id'=>$user->id,'remember'=>false,'context'=>'hospital','issued_at'=>time()]);
            return redirect()->route('two-factor.challenge');
        }
        return redirect()->route('hospital.dashboard');
    }

    public function logout(Request $request): RedirectResponse { Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect()->route('hospital.login'); }

    public function dashboard(Request $request): View
    {
        $hospital=$request->user()->hospital;
        $requests=BloodRequest::where('hospital_id',$hospital->id)->with(['allocations.unit','allocations.reactions'])->latest()->get();
        $reactions=AdverseReaction::query()->where('hospital_id',$hospital->id)->with(['allocation.unit'])->latest('occurred_at')->limit(20)->get();
        $notifications=$request->user()->notifications()->latest()->limit(8)->get();
        $unreadNotificationCount=$request->user()->unreadNotifications()->count();
        return view('hospital.dashboard',compact('hospital','requests','reactions','notifications','unreadNotificationCount'));
    }

    public function markNotificationRead(Request $request, string $notification): RedirectResponse
    {
        $item=$request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();
        return back()->with('status', __('bloodcare.notifications.read_saved'));
    }

    public function markAllNotificationsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();
        return back()->with('status', __('bloodcare.notifications.all_read_saved'));
    }

    public function storeRequest(Request $request, BloodCareNotificationService $notifications): RedirectResponse
    {
        $data=$request->validate([
            'patient_reference'=>['required','string','max:80'],
            'blood_group'=>['required',Rule::in(['A+','A-','B+','B-','AB+','AB-','O+','O-'])],
            'component_type'=>['required',Rule::in(['whole_blood','red_cells','plasma','platelets','cryoprecipitate'])],
            'requires_leukoreduced'=>['nullable','boolean'],
            'requires_irradiated'=>['nullable','boolean'],
            'requires_washed'=>['nullable','boolean'],
            'quantity'=>['required','integer','min:1','max:20'],
            'priority'=>['required',Rule::in(['routine','urgent','emergency'])],
            'clinical_note'=>['nullable','string','max:2000'],
        ]);
        foreach (['requires_leukoreduced','requires_irradiated','requires_washed'] as $modifier) {
            $data[$modifier] = (bool) ($data[$modifier] ?? false);
        }
        $user=$request->user();
        $bloodRequest=BloodRequest::create(['reference'=>BloodRequest::generateReference(),'hospital_id'=>$user->hospital_id,'requested_by'=>$user->id,...$data,'status'=>'pending']);
        ActivityLog::record(['type'=>'Hospital Request','action'=>'Hospital blood request submitted','subject_type'=>BloodRequest::class,'subject_id'=>$bloodRequest->id,'user_id'=>$user->id,'result'=>'Pending','details'=>"{$bloodRequest->reference}; {$data['blood_group']}; {$data['component_type']}; qty {$data['quantity']}",'source'=>'hospital-portal']);
        $notifications->hospitalRequestSubmitted($bloodRequest->load('hospital'));
        return back()->with('status', __('bloodcare.national.portal.request_saved'));
    }

    public function receive(Request $request, BloodAllocation $allocation): RedirectResponse
    {
        $a=$this->ownedAllocation($request,$allocation); if($a->status!=='dispatched') throw ValidationException::withMessages(['allocation'=>'Only dispatched units can be received.']);
        $a->update(['status'=>'received','received_at'=>now()]);
        ActivityLog::record(['type'=>'Hospital Request','action'=>'Hospital confirmed blood receipt','subject_type'=>BloodAllocation::class,'subject_id'=>$a->id,'user_id'=>$request->user()->id,'result'=>'Received','details'=>"{$a->request->reference}; {$a->unit->unit_number}",'source'=>'hospital-portal']);
        return back()->with('status', __('bloodcare.national.portal.receipt_saved'));
    }

    public function transfuse(Request $request, BloodAllocation $allocation): RedirectResponse
    {
        $a=$this->ownedAllocation($request,$allocation); if($a->status!=='received') throw ValidationException::withMessages(['allocation'=>'Receive the unit before recording transfusion.']);
        $a->update(['status'=>'transfused','transfused_at'=>now()]);
        $allComplete=$a->request->allocations()->where('status','!=','transfused')->doesntExist();
        $a->request->update(['status'=>$allComplete?'transfused':'partially_transfused']);
        ActivityLog::record(['type'=>'Transfusion','action'=>'Hospital recorded transfusion','subject_type'=>BloodAllocation::class,'subject_id'=>$a->id,'user_id'=>$request->user()->id,'result'=>'Transfused','details'=>"{$a->request->reference}; {$a->unit->unit_number}",'source'=>'hospital-portal']);
        return back()->with('status', __('bloodcare.national.portal.transfusion_saved'));
    }

    public function reaction(Request $request, BloodAllocation $allocation, BloodCareNotificationService $notifications): RedirectResponse
    {
        $a=$this->ownedAllocation($request,$allocation); if(!in_array($a->status,['received','transfused'],true)) throw ValidationException::withMessages(['allocation'=>'Reaction reports require a received or transfused unit.']);
        $data=$request->validate([
            'severity'=>['required',Rule::in(['mild','moderate','severe','life_threatening'])],
            'suspected_reaction_type'=>['nullable',Rule::in(AdverseReaction::REACTION_TYPES)],
            'symptoms'=>['required','string','max:3000'],
            'action_taken'=>['required','string','max:3000'],
            'occurred_at'=>['required','date','before_or_equal:now'],
        ]);
        $reaction=AdverseReaction::create(['reference'=>AdverseReaction::generateReference(),'blood_allocation_id'=>$a->id,'hospital_id'=>$request->user()->hospital_id,'reported_by'=>$request->user()->id,'status'=>'reported',...$data]);
        ActivityLog::record(['type'=>'Haemovigilance','action'=>'Adverse transfusion reaction reported','subject_type'=>BloodAllocation::class,'subject_id'=>$a->id,'user_id'=>$request->user()->id,'result'=>ucfirst(str_replace('_',' ',$data['severity'])),'details'=>"{$a->unit->unit_number}; {$data['symptoms']}",'source'=>'hospital-portal']);
        $notifications->adverseReactionReported($reaction);
        return back()->with('status', __('bloodcare.national.portal.reaction_saved'));
    }

    private function ownedAllocation(Request $request,BloodAllocation $allocation): BloodAllocation
    {
        return BloodAllocation::query()->whereKey($allocation->id)->whereHas('request',fn($q)=>$q->where('hospital_id',$request->user()->hospital_id))->with(['request','unit'])->firstOrFail();
    }
}
