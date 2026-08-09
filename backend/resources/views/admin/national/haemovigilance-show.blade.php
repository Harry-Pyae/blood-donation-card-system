@extends(backpack_view('blank'))
@section('title', $reaction->reference.' · '.__('bloodcare.national.haemovigilance.title'))
@push('before_styles') @include('admin.partials.favicon') @endpush
@push('after_styles') <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}?v={{ filemtime(public_path('css/bloodcare-admin.css')) }}"> @endpush
@push('after_scripts') <script src="{{ asset('js/bloodcare-national-filters.js') }}?v={{ filemtime(public_path('js/bloodcare-national-filters.js')) }}" defer></script> @endpush

@section('content')
@php
    $allocation=$reaction->allocation; $bloodRequest=$allocation?->request; $unit=$allocation?->unit;
    $statusOptions=[]; foreach(\App\Models\AdverseReaction::STATUSES as $value) $statusOptions[$value]=__('bloodcare.national.haemovigilance.statuses.'.$value);
    $reactionOptions=[''=>__('bloodcare.national.haemovigilance.not_classified')]; foreach(\App\Models\AdverseReaction::REACTION_TYPES as $value) $reactionOptions[$value]=__('bloodcare.national.haemovigilance.reaction_types.'.$value);
    $imputabilityOptions=[''=>__('bloodcare.national.haemovigilance.not_assessed')]; foreach(\App\Models\AdverseReaction::IMPUTABILITY as $value) $imputabilityOptions[$value]=__('bloodcare.national.haemovigilance.imputability_options.'.$value);
    $outcomeOptions=[''=>__('bloodcare.national.haemovigilance.not_assessed')]; foreach(\App\Models\AdverseReaction::OUTCOMES as $value) $outcomeOptions[$value]=__('bloodcare.national.haemovigilance.outcomes.'.$value);
@endphp
<div class="bc-admin-page bc-module-page bc-national-page bc-haemovigilance-page bc-haemo-show-page">
    <header class="bc-page-heading">
        <div class="bc-module-title"><a class="bc-haemo-back" href="{{ route('bloodcare.admin.haemovigilance') }}" aria-label="{{ __('bloodcare.national.haemovigilance.back_to_queue') }}"><i class="la la-arrow-left"></i></a><div>
            <p class="bc-eyebrow">{{ __('bloodcare.national.haemovigilance.case_details') }}</p>
            <h1>{{ $reaction->reference }}</h1>
            <p>{{ $reaction->hospital?->name ?: __('bloodcare.national.common.not_provided') }} · {{ $unit?->unit_number ?: __('bloodcare.national.common.not_provided') }}</p>
        </div></div>
        @include('admin.partials.utility-controls')
    </header>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <section class="bc-panel bc-haemo-case-hero {{ in_array($reaction->severity,['severe','life_threatening'],true) && $reaction->status !== 'closed' ? 'is-serious' : '' }}">
        <div><span class="bc-haemo-case-mark"><i class="la la-heartbeat"></i></span><div><small>{{ __('bloodcare.national.haemovigilance.case') }}</small><strong>{{ $reaction->reference }}</strong><p>{{ $bloodRequest?->reference ?: '—' }} · {{ $bloodRequest?->patient_reference ?: '—' }}</p></div></div>
        <div class="bc-haemo-case-badges"><span class="bc-status bc-haemo-severity-{{ $reaction->severity }}">{{ __('bloodcare.national.portal.'.$reaction->severity) }}</span><span class="bc-status bc-haemo-status-{{ $reaction->status }}">{{ __('bloodcare.national.haemovigilance.statuses.'.$reaction->status) }}</span></div>
    </section>

    <div class="bc-haemo-show-grid">
        <section class="bc-panel bc-haemo-section bc-haemo-clinical-report">
            <header><span><i class="la la-file-medical-alt"></i></span><div><h2>{{ __('bloodcare.national.haemovigilance.clinical_report') }}</h2><p>{{ __('bloodcare.national.haemovigilance.clinical_report_help') }}</p></div></header>
            <dl class="bc-haemo-detail-grid bc-haemo-show-details">
                <div><dt>{{ __('bloodcare.national.haemovigilance.patient_reference') }}</dt><dd>{{ $bloodRequest?->patient_reference ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                <div><dt>{{ __('bloodcare.national.haemovigilance.occurred_at') }}</dt><dd>{{ $reaction->occurred_at?->format('Y-m-d H:i') ?: '—' }}</dd></div>
                <div><dt>{{ __('bloodcare.national.haemovigilance.reported_by') }}</dt><dd>{{ $reaction->reporter?->name ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                <div><dt>{{ __('bloodcare.national.haemovigilance.suspected_type') }}</dt><dd>{{ $reaction->suspected_reaction_type ? __('bloodcare.national.haemovigilance.reaction_types.'.$reaction->suspected_reaction_type) : __('bloodcare.national.haemovigilance.not_classified') }}</dd></div>
                <div class="bc-haemo-wide"><dt>{{ __('bloodcare.national.haemovigilance.symptoms') }}</dt><dd>{{ $reaction->symptoms }}</dd></div>
                <div class="bc-haemo-wide"><dt>{{ __('bloodcare.national.haemovigilance.immediate_action') }}</dt><dd>{{ $reaction->action_taken }}</dd></div>
            </dl>
        </section>

        <aside class="bc-panel bc-haemo-section bc-haemo-linked-records">
            <header><span><i class="la la-link"></i></span><div><h2>{{ __('bloodcare.national.haemovigilance.linked_records') }}</h2><p>{{ __('bloodcare.national.haemovigilance.linked_records_help') }}</p></div></header>
            <dl>
                <div><dt>{{ __('bloodcare.national.hospitals.hospital') }}</dt><dd>{{ $reaction->hospital?->name ?: '—' }}</dd></div>
                <div><dt>{{ __('bloodcare.national.blood_requests.request_code') }}</dt><dd>{{ $bloodRequest?->reference ?: '—' }}</dd></div>
                <div><dt>{{ __('bloodcare.inventory.unit_number') }}</dt><dd>{{ $unit?->unit_number ?: '—' }}</dd></div>
                <div><dt>{{ __('bloodcare.inventory.donation_reference') }}</dt><dd>{{ $unit?->donation?->reference ?: '—' }}</dd></div>
            </dl>
            @if($unit)<a class="btn bc-btn-outline bc-haemo-unit-link" href="{{ route('bloodcare.admin.inventory',['unit'=>$unit->unit_number]) }}"><i class="la la-qrcode"></i> {{ __('bloodcare.national.haemovigilance.open_unit_trace') }}</a>@endif
        </aside>
    </div>

    <section class="bc-panel bc-haemo-section bc-haemo-traceback">
        <header><span><i class="la la-sitemap"></i></span><div><h2>{{ __('bloodcare.national.haemovigilance.traceback_title') }}</h2><p>{{ __('bloodcare.national.haemovigilance.traceback_help') }}</p></div></header>
        @if($unit?->donation)
            <p class="bc-haemo-donation-reference">{{ __('bloodcare.inventory.donation_reference') }}: <strong>{{ $unit->donation->reference }}</strong></p>
            <div class="bc-haemo-related-units">@foreach($unit->donation->bloodUnits->sortBy('created_at') as $relatedUnit)<span class="{{ $relatedUnit->id === $unit->id ? 'is-current' : '' }}"><strong>{{ $relatedUnit->unit_number }}</strong><small>{{ __('bloodcare.national.components.types.'.$relatedUnit->component_type) }} · {{ __('bloodcare.traceability.statuses.'.$relatedUnit->status) }}</small></span>@endforeach</div>
        @else<p class="bc-haemo-traceback-empty">{{ __('bloodcare.national.haemovigilance.traceback_unavailable') }}</p>@endif
    </section>

    <section class="bc-panel bc-haemo-section bc-haemo-investigation-panel" id="investigation">
        <header><span><i class="la la-search-plus"></i></span><div><h2>{{ $reaction->status === 'closed' ? __('bloodcare.national.haemovigilance.closed_summary') : __('bloodcare.national.haemovigilance.review_case') }}</h2><p>{{ __('bloodcare.national.haemovigilance.investigation_help') }}</p></div></header>
        @if($reaction->status === 'closed')
            <dl class="bc-haemo-detail-grid bc-haemo-closed-grid">
                <div><dt>{{ __('bloodcare.national.haemovigilance.final_type') }}</dt><dd>{{ __('bloodcare.national.haemovigilance.reaction_types.'.$reaction->reaction_type) }}</dd></div>
                <div><dt>{{ __('bloodcare.national.haemovigilance.imputability') }}</dt><dd>{{ __('bloodcare.national.haemovigilance.imputability_options.'.$reaction->imputability) }}</dd></div>
                <div><dt>{{ __('bloodcare.national.haemovigilance.outcome') }}</dt><dd>{{ __('bloodcare.national.haemovigilance.outcomes.'.$reaction->outcome) }}</dd></div>
                <div><dt>{{ __('bloodcare.national.haemovigilance.closed_at') }}</dt><dd>{{ $reaction->closed_at?->format('Y-m-d H:i') ?: '—' }} · {{ $reaction->closedBy?->name ?: '—' }}</dd></div>
                <div class="bc-haemo-wide"><dt>{{ __('bloodcare.national.haemovigilance.investigation_notes') }}</dt><dd>{{ $reaction->investigation_notes }}</dd></div>
                <div class="bc-haemo-wide"><dt>{{ __('bloodcare.national.haemovigilance.corrective_action') }}</dt><dd>{{ $reaction->corrective_action }}</dd></div>
            </dl>
            <div class="bc-haemo-lock-note"><i class="la la-lock"></i><span>{{ __('bloodcare.national.haemovigilance.closed_locked') }}</span></div>
        @else
            <form method="POST" action="{{ route('bloodcare.admin.haemovigilance.update',$reaction) }}" class="bc-haemo-review-form">@csrf @method('PATCH')
                <input type="hidden" name="reaction_id" value="{{ $reaction->id }}">
                @include('admin.national.partials.haemovigilance-select', ['id'=>'bc-haemo-review-status','name'=>'status','label'=>__('bloodcare.national.common.status'),'selected'=>$reaction->status,'options'=>$statusOptions,'icon'=>'la-tasks','required'=>true])
                @include('admin.national.partials.haemovigilance-select', ['id'=>'bc-haemo-review-type','name'=>'reaction_type','label'=>__('bloodcare.national.haemovigilance.final_type'),'selected'=>$reaction->reaction_type ?? '','options'=>$reactionOptions,'icon'=>'la-notes-medical'])
                @include('admin.national.partials.haemovigilance-select', ['id'=>'bc-haemo-review-imputability','name'=>'imputability','label'=>__('bloodcare.national.haemovigilance.imputability'),'selected'=>$reaction->imputability ?? '','options'=>$imputabilityOptions,'icon'=>'la-link'])
                @include('admin.national.partials.haemovigilance-select', ['id'=>'bc-haemo-review-outcome','name'=>'outcome','label'=>__('bloodcare.national.haemovigilance.outcome'),'selected'=>$reaction->outcome ?? '','options'=>$outcomeOptions,'icon'=>'la-user-check'])
                <label class="bc-haemo-textarea"><span>{{ __('bloodcare.national.haemovigilance.investigation_notes') }}</span><textarea name="investigation_notes" rows="5" maxlength="5000" placeholder="{{ __('bloodcare.national.haemovigilance.investigation_placeholder') }}">{{ old('investigation_notes',$reaction->investigation_notes) }}</textarea></label>
                <label class="bc-haemo-textarea"><span>{{ __('bloodcare.national.haemovigilance.corrective_action') }}</span><textarea name="corrective_action" rows="5" maxlength="5000" placeholder="{{ __('bloodcare.national.haemovigilance.corrective_placeholder') }}">{{ old('corrective_action',$reaction->corrective_action) }}</textarea></label>
                <div class="bc-haemo-form-footer"><p><i class="la la-lock"></i> {{ __('bloodcare.national.haemovigilance.close_warning') }}</p><button class="btn bc-btn-primary" type="submit"><i class="la la-save"></i> {{ __('bloodcare.national.haemovigilance.save_review') }}</button></div>
            </form>
        @endif
    </section>
</div>
@endsection
