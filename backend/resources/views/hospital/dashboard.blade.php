<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $hospital->name }} · BloodCare</title><link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bloodcare-backpack/css/line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}">
</head>
<body class="bc-hospital-body"><div class="bc-hospital-shell">
<aside class="bc-hospital-sidebar">
    <div class="bc-hospital-brand"><span><i class="la la-heartbeat"></i></span><div><strong>BloodCare</strong><small>{{ __('bloodcare.national.portal.hospital_portal') }}</small></div></div>
    @include('hospital.language-switch')
    <nav>
        <a class="active" href="#overview"><i class="la la-th-large"></i> {{ __('bloodcare.national.portal.dashboard') }}</a>
        <a href="#new-request"><i class="la la-plus-circle"></i> {{ __('bloodcare.national.portal.new_request') }}</a>
        <a href="#requests"><i class="la la-tint"></i> {{ __('bloodcare.national.portal.blood_requests') }}</a>
        <a href="#transfusions"><i class="la la-notes-medical"></i> {{ __('bloodcare.national.portal.transfusions') }}</a>
        <a href="#haemovigilance"><i class="la la-exclamation-triangle"></i> {{ __('bloodcare.national.portal.reactions') }}</a>
        <a href="#notifications"><i class="la la-bell"></i> {{ __('bloodcare.notifications.title') }} @if($unreadNotificationCount > 0)<span class="bc-hospital-notification-badge">{{ $unreadNotificationCount }}</span>@endif</a>
        <a href="{{ route('two-factor.manage') }}"><i class="la la-user-shield"></i> {{ __('bloodcare.two_factor.account_security') }}</a>
    </nav>
    <form method="POST" action="{{ route('hospital.logout') }}">@csrf<button class="btn btn-outline-secondary w-100">{{ __('bloodcare.national.portal.sign_out') }}</button></form>
</aside>
<main class="bc-hospital-content">
    <header id="overview"><p class="bc-eyebrow">{{ $hospital->code }}</p><h1>{{ $hospital->name }}</h1><p>{{ __('bloodcare.national.portal.privacy') }}</p></header>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <section class="bc-module-metrics">
        @foreach([[__('bloodcare.national.portal.requests_metric'),$requests->count(),'blue'],[__('bloodcare.national.portal.pending'),$requests->where('status','pending')->count(),'amber'],[__('bloodcare.national.portal.allocated'),$requests->whereIn('status',['allocated','partially_allocated'])->count(),'green'],[__('bloodcare.national.portal.transfused'),$requests->where('status','transfused')->count(),'red']] as [$label,$value,$tone])
            <article class="bc-mini-metric"><span class="bc-mini-dot bc-dot-{{ $tone }}"></span><div><small>{{ $label }}</small><strong>{{ $value }}</strong></div></article>
        @endforeach
    </section>

    <section id="notifications" class="bc-panel bc-hospital-notification-panel">
        <div class="bc-panel-heading">
            <div><h2>{{ __('bloodcare.notifications.title') }}</h2><p>{{ __('bloodcare.notifications.hospital_subtitle') }}</p></div>
            @if($unreadNotificationCount > 0)
                <form method="POST" action="{{ route('hospital.notifications.read-all') }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-secondary">{{ __('bloodcare.notifications.mark_all_read') }}</button></form>
            @endif
        </div>
        <div class="bc-hospital-notification-list">
            @forelse($notifications as $notification)
                @php
                    $data=is_array($notification->data)?$notification->data:[];
                    $parameters=is_array($data['parameters']??null)?$data['parameters']:[];
                @endphp
                <article class="{{ $notification->read_at ? 'is-read' : 'is-unread' }}">
                    <span><i class="la la-bell"></i></span>
                    <div><strong>{{ __($data['title_key']??'bloodcare.notifications.fallback_title',$parameters) }}</strong><p>{{ __($data['message_key']??'bloodcare.notifications.fallback_message',$parameters) }}</p><small>{{ $notification->created_at?->diffForHumans() }}</small></div>
                    @unless($notification->read_at)<form method="POST" action="{{ route('hospital.notifications.read',$notification->id) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-secondary">{{ __('bloodcare.notifications.mark_read') }}</button></form>@endunless
                </article>
            @empty
                <p class="bc-hospital-notification-empty">{{ __('bloodcare.notifications.empty_text') }}</p>
            @endforelse
        </div>
    </section>

    <section id="new-request" class="bc-panel bc-national-form-panel">
        <div class="bc-panel-heading"><div><h2>{{ __('bloodcare.national.portal.create_request') }}</h2><p>{{ __('bloodcare.national.portal.request_help') }}</p></div></div>
        <form method="POST" action="{{ route('hospital.requests.store') }}" class="bc-hospital-request-form">@csrf
            <label>{{ __('bloodcare.national.portal.patient_reference') }}<input name="patient_reference" required maxlength="80"></label>
            <label>{{ __('bloodcare.national.common.blood_group') }}<select name="blood_group" required>@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g)<option>{{ $g }}</option>@endforeach</select></label>
            <label>{{ __('bloodcare.national.portal.component') }}<select name="component_type" required><option value="red_cells">{{ __('bloodcare.national.components.types.red_cells') }}</option><option value="plasma">{{ __('bloodcare.national.components.types.plasma') }}</option><option value="platelets">{{ __('bloodcare.national.components.types.platelets') }}</option><option value="cryoprecipitate">{{ __('bloodcare.national.components.types.cryoprecipitate') }}</option><option value="whole_blood">{{ __('bloodcare.national.components.types.whole_blood') }}</option></select></label>
            <label>{{ __('bloodcare.national.portal.quantity') }}<input type="number" name="quantity" value="1" min="1" max="20" required></label>
            <label>{{ __('bloodcare.national.portal.priority') }}<select name="priority"><option value="routine">{{ __('bloodcare.national.portal.routine') }}</option><option value="urgent">{{ __('bloodcare.national.portal.urgent') }}</option><option value="emergency">{{ __('bloodcare.national.portal.emergency') }}</option></select></label>
            <fieldset class="bc-hospital-modifier-fieldset"><legend>{{ __('bloodcare.national.portal.special_requirements') }}</legend><p>{{ __('bloodcare.national.portal.special_requirements_help') }}</p><div>@foreach(['leukoreduced','irradiated','washed'] as $modifier)<label><input type="checkbox" name="requires_{{ $modifier }}" value="1"><span>{{ __('bloodcare.national.components.modifiers.'.$modifier) }}</span></label>@endforeach</div></fieldset>
            <label class="bc-form-wide">{{ __('bloodcare.national.portal.clinical_note') }}<textarea name="clinical_note" rows="2" maxlength="2000"></textarea></label>
            <button class="btn bc-btn-primary">{{ __('bloodcare.national.portal.submit_request') }}</button>
        </form>
    </section>

    <span id="transfusions" class="bc-anchor-target" aria-hidden="true"></span>
    <section id="requests" class="bc-panel bc-module-table-panel">
        <div class="bc-panel-heading"><h2>{{ __('bloodcare.national.portal.request_history') }}</h2></div>
        <div class="table-responsive"><table class="table table-vcenter bc-module-table"><thead><tr><th>{{ __('bloodcare.national.portal.reference') }}</th><th>{{ __('bloodcare.national.portal.patient') }}</th><th>{{ __('bloodcare.national.portal.need') }}</th><th>{{ __('bloodcare.national.common.status') }}</th><th>{{ __('bloodcare.national.portal.allocated_units') }}</th></tr></thead><tbody>
        @forelse($requests as $req)
            <tr><td><strong>{{ $req->reference }}</strong><br><small>{{ __('bloodcare.national.portal.'.$req->priority) }}</small></td><td>{{ $req->patient_reference }}</td><td>{{ $req->blood_group }} · {{ __('bloodcare.national.components.types.'.$req->component_type) }} × {{ $req->quantity }}@php $requiredModifiers = collect(['leukoreduced','irradiated','washed'])->filter(fn ($modifier) => $req->{'requires_'.$modifier}); @endphp @if($requiredModifiers->isNotEmpty())<small class="bc-request-modifier-summary">{{ $requiredModifiers->map(fn ($modifier) => __('bloodcare.national.components.modifiers.'.$modifier))->join(' · ') }}</small>@endif</td><td><span class="bc-status bc-status-{{ $req->status }}">{{ __('bloodcare.national.common.'.$req->status) }}</span>@if($req->status === 'rejected' && $req->decision_note)<small class="bc-hospital-decision-note">{{ __('bloodcare.national.portal.decision_note') }}: {{ $req->decision_note }}</small>@endif</td><td>
            @forelse($req->allocations as $a)
                <div class="bc-hospital-unit"><strong>{{ $a->unit?->unit_number }}</strong> · {{ __('bloodcare.national.common.'.$a->status) }}
                @if($a->status==='dispatched')
                    <form method="POST" action="{{ route('hospital.allocations.receive',$a) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-primary">{{ __('bloodcare.national.portal.confirm_receipt') }}</button></form>
                @elseif($a->status==='received')
                    <form method="POST" action="{{ route('hospital.allocations.transfuse',$a) }}">@csrf @method('PATCH')<button class="btn btn-sm bc-btn-primary">{{ __('bloodcare.national.portal.record_transfusion') }}</button></form>
                @endif
                @if(in_array($a->status,['received','transfused'],true))
                    <details><summary>{{ __('bloodcare.national.portal.report_reaction') }}</summary><form method="POST" action="{{ route('hospital.reactions.store',$a) }}" class="bc-reaction-form">@csrf
                        <select name="severity" required><option value="mild">{{ __('bloodcare.national.portal.mild') }}</option><option value="moderate">{{ __('bloodcare.national.portal.moderate') }}</option><option value="severe">{{ __('bloodcare.national.portal.severe') }}</option><option value="life_threatening">{{ __('bloodcare.national.portal.life_threatening') }}</option></select>
                        <select name="suspected_reaction_type"><option value="">{{ __('bloodcare.national.haemovigilance.not_classified') }}</option>@foreach(\App\Models\AdverseReaction::REACTION_TYPES as $reactionType)<option value="{{ $reactionType }}">{{ __('bloodcare.national.haemovigilance.reaction_types.'.$reactionType) }}</option>@endforeach</select>
                        <input name="occurred_at" type="datetime-local" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                        <textarea name="symptoms" required placeholder="{{ __('bloodcare.national.portal.symptoms') }}"></textarea><textarea name="action_taken" required placeholder="{{ __('bloodcare.national.portal.action_taken') }}"></textarea>
                        <button class="btn btn-sm btn-outline-danger">{{ __('bloodcare.national.portal.submit_reaction') }}</button>
                    </form></details>
                @endif
                </div>
            @empty — @endforelse
            </td></tr>
        @empty <tr><td colspan="5">{{ __('bloodcare.national.portal.no_requests') }}</td></tr>@endforelse
        </tbody></table></div>
    </section>
    <section id="haemovigilance" class="bc-panel bc-haemovigilance-note">
        <div class="bc-panel-heading"><div><h2>{{ __('bloodcare.national.portal.haemovigilance_reports') }}</h2><p>{{ __('bloodcare.national.portal.haemovigilance_reports_help') }}</p></div></div>
        <div class="bc-hospital-haemo-list">
            @forelse($reactions as $reaction)
                <article class="bc-hospital-haemo-case">
                    <header><div><strong>{{ $reaction->reference }}</strong><small>{{ $reaction->allocation?->unit?->unit_number }} · {{ $reaction->occurred_at?->format('Y-m-d H:i') }}</small></div><span class="bc-status bc-haemo-status-{{ $reaction->status }}">{{ __('bloodcare.national.haemovigilance.statuses.'.$reaction->status) }}</span></header>
                    <dl>
                        <div><dt>{{ __('bloodcare.national.haemovigilance.severity') }}</dt><dd>{{ __('bloodcare.national.portal.'.$reaction->severity) }}</dd></div>
                        <div><dt>{{ __('bloodcare.national.haemovigilance.suspected_type') }}</dt><dd>{{ $reaction->suspected_reaction_type ? __('bloodcare.national.haemovigilance.reaction_types.'.$reaction->suspected_reaction_type) : __('bloodcare.national.haemovigilance.not_classified') }}</dd></div>
                        <div><dt>{{ __('bloodcare.national.haemovigilance.final_type') }}</dt><dd>{{ $reaction->reaction_type ? __('bloodcare.national.haemovigilance.reaction_types.'.$reaction->reaction_type) : __('bloodcare.national.haemovigilance.not_classified') }}</dd></div>
                        <div><dt>{{ __('bloodcare.national.haemovigilance.imputability') }}</dt><dd>{{ $reaction->imputability ? __('bloodcare.national.haemovigilance.imputability_options.'.$reaction->imputability) : __('bloodcare.national.haemovigilance.not_assessed') }}</dd></div>
                        <div><dt>{{ __('bloodcare.national.haemovigilance.outcome') }}</dt><dd>{{ $reaction->outcome ? __('bloodcare.national.haemovigilance.outcomes.'.$reaction->outcome) : __('bloodcare.national.haemovigilance.not_assessed') }}</dd></div>
                        <div class="bc-haemo-wide"><dt>{{ __('bloodcare.national.haemovigilance.corrective_action') }}</dt><dd>{{ $reaction->corrective_action ?: __('bloodcare.national.haemovigilance.not_assessed') }}</dd></div>
                    </dl>
                </article>
            @empty
                <p class="bc-hospital-notification-empty">{{ __('bloodcare.national.portal.no_reactions') }}</p>
            @endforelse
        </div>
        <p class="bc-haemo-privacy-note"><i class="la la-shield-alt"></i> {{ __('bloodcare.national.portal.haemovigilance_help') }}</p>
    </section>
</main></div></body></html>
