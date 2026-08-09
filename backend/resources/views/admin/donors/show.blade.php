@extends(backpack_view('blank'))

@section('title', $donor->full_name)

@push('before_styles')
    @include('admin.partials.favicon')
@endpush

@push('after_styles')
    <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}?v={{ filemtime(public_path('css/bloodcare-admin.css')) }}">
@endpush

@push('after_scripts')
    <script src="{{ asset('js/bloodcare-donor-form.js') }}?v={{ filemtime(public_path('js/bloodcare-donor-form.js')) }}" defer></script>
@endpush

@section('content')
    <div class="bc-admin-page bc-donor-record-page" data-bc-donor-details>
        <header class="bc-page-heading bc-record-heading">
            <div class="bc-module-title">
                <span class="bc-module-icon"><i class="la la-user"></i></span>
                <div>
                    <p class="bc-eyebrow">{{ __('bloodcare.donors.details_eyebrow') }}</p>
                    <h1>{{ $donor->full_name }}</h1>
                    <p>{{ $donor->reference }} · {{ __('bloodcare.donor_details.complete_record') }}</p>
                </div>
            </div>
            <div class="bc-heading-tools">
                @include('admin.partials.utility-controls')
                <div class="bc-heading-actions">
                    <a class="btn bc-btn-outline" href="{{ route('bloodcare.admin.donors') }}"><i class="la la-arrow-left"></i> {{ __('bloodcare.donor_details.all_donors') }}</a>
                    <a class="btn bc-btn-primary" href="{{ route('bloodcare.admin.donors.edit', $donor) }}"><i class="la la-pen"></i> {{ __('bloodcare.donors.edit') }}</a>
                </div>
            </div>
        </header>

        @if (session('status'))
            <div class="bc-record-success" role="status"><i class="la la-check-circle"></i> {{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="bc-form-alert-error bc-record-alert" role="alert"><strong>{{ __('bloodcare.donor_details.fix_errors') }}</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <section class="bc-panel bc-donor-profile-hero">
            <div class="bc-donor-profile-main">
                <span class="bc-donor-profile-avatar">{{ mb_strtoupper(mb_substr($donor->full_name, 0, 1, 'UTF-8'), 'UTF-8') }}</span>
                <div><h2>{{ $donor->full_name }}</h2><p>{{ $donor->reference }} · {{ $donor->phone }}</p><div class="bc-profile-badges"><span class="bc-group-badge">{{ $donor->blood_group }}</span><span class="bc-status bc-status-{{ $donor->status }}">{{ __('bloodcare.dashboard.status.'.$donor->status) }}</span><span class="bc-status bc-status-{{ $donor->eligibility_status }}">{{ __('bloodcare.donors.'.$donor->eligibility_status) }}</span></div></div>
            </div>
            <div class="bc-donor-profile-stats">
                <div><small>{{ __('bloodcare.donor_details.total_donations') }}</small><strong>{{ $donationCount }}</strong></div>
                <div><small>{{ __('bloodcare.donor_details.accepted_donations') }}</small><strong>{{ $acceptedDonationCount }}</strong></div>
                <div><small>{{ __('bloodcare.donor_details.medical_checks') }}</small><strong>{{ $screeningCount }}</strong></div>
                <div><small>{{ __('bloodcare.donors.next_eligible') }}</small><strong>{{ $donor->next_eligible_date?->format('d M Y') ?? __('bloodcare.donors.eligible_now') }}</strong></div>
            </div>
        </section>

        <div class="bc-record-section-scope" data-bc-section-scope data-bc-initial-section="{{ $errors->any() ? 'clinical' : 'overview' }}">
            <nav class="bc-record-section-nav" data-bc-section-nav role="tablist" aria-label="{{ __('bloodcare.donor_details.section_navigation') }}">
                <button class="is-active" type="button" role="tab" aria-selected="true" aria-controls="bc-details-section-overview" data-bc-section-button data-bc-section-target="overview">
                    <i class="la la-user"></i><span>{{ __('bloodcare.donor_details.nav_overview') }}</span>
                </button>
                <button type="button" role="tab" aria-selected="false" aria-controls="bc-details-section-clinical" data-bc-section-button data-bc-section-target="clinical">
                    <i class="la la-heartbeat"></i><span>{{ __('bloodcare.donor_details.nav_clinical') }}</span>
                </button>
                <button type="button" role="tab" aria-selected="false" aria-controls="donation-history" data-bc-section-button data-bc-section-target="donations">
                    <i class="la la-tint"></i><span>{{ __('bloodcare.donor_details.nav_donation_history') }}</span>
                </button>
                <button type="button" role="tab" aria-selected="false" aria-controls="screening-history" data-bc-section-button data-bc-section-target="screenings">
                    <i class="la la-notes-medical"></i><span>{{ __('bloodcare.donor_details.nav_screening_history') }}</span>
                </button>
            </nav>

            <div class="bc-record-section-panel" id="bc-details-section-overview" role="tabpanel" data-bc-section-panel="overview" data-bc-section-hash="overview">

        <div class="bc-record-two-column">
            <section class="bc-panel bc-record-section">
                <header class="bc-record-section-heading"><span><i class="la la-address-card"></i></span><div><h2>{{ __('bloodcare.donor_details.personal_contact') }}</h2><p>{{ __('bloodcare.donor_details.personal_contact_help') }}</p></div></header>
                <dl class="bc-detail-list">
                    <div><dt>{{ __('bloodcare.donors.date_of_birth') }}</dt><dd>{{ $donor->date_of_birth?->format('d M Y') }}</dd></div>
                    <div><dt>{{ __('bloodcare.donors.gender') }}</dt><dd>{{ __('bloodcare.donors.'.$donor->gender) }}</dd></div>
                    <div><dt>{{ __('bloodcare.donors.phone') }}</dt><dd>{{ $donor->phone }}</dd></div>
                    <div><dt>{{ __('bloodcare.donors.email') }}</dt><dd>{{ $donor->email ?: __('bloodcare.donors.not_recorded') }}</dd></div>
                    <div><dt>{{ __('bloodcare.donor_details.emergency_contact') }}</dt><dd>{{ $donor->emergency_contact }}</dd></div>
                    <div><dt>{{ __('bloodcare.donors.identity') }}</dt><dd>{{ $donor->identity_number }}</dd></div>
                    <div class="bc-detail-span"><dt>{{ __('bloodcare.donors.address') }}</dt><dd>{{ $donor->address }}</dd></div>
                </dl>
            </section>

            <section class="bc-panel bc-record-section">
                <header class="bc-record-section-heading"><span><i class="la la-user-circle"></i></span><div><h2>{{ __('bloodcare.donor_details.user_section') }}</h2><p>{{ __('bloodcare.donor_details.user_section_help') }}</p></div></header>
                @if ($donor->user)
                    <dl class="bc-detail-list">
                        <div><dt>{{ __('bloodcare.users.full_name') }}</dt><dd>{{ $donor->user->name }}</dd></div>
                        <div><dt>{{ __('bloodcare.users.email') }}</dt><dd>{{ $donor->user->email }}</dd></div>
                        <div><dt>{{ __('bloodcare.users.role') }}</dt><dd>{{ __('bloodcare.users.user_role') }}</dd></div>
                        <div><dt>{{ __('bloodcare.users.status') }}</dt><dd>{{ $donor->user->is_banned ? __('bloodcare.users.banned') : __('bloodcare.users.active') }}</dd></div>
                    </dl>
                @else
                    <div class="bc-empty-inline"><i class="la la-unlink"></i><div><strong>{{ __('bloodcare.donor_details.no_linked_user') }}</strong><p>{{ __('bloodcare.donor_details.no_linked_user_help') }}</p></div></div>
                @endif
                <p class="bc-field-note"><i class="la la-lock"></i> {{ __('bloodcare.donor_details.user_boundary_help') }}</p>
            </section>
        </div>

        <div class="bc-record-two-column">
            <section class="bc-panel bc-record-section">
                <header class="bc-record-section-heading"><span><i class="la la-tint"></i></span><div><h2>{{ __('bloodcare.donor_details.donation_profile') }}</h2><p>{{ __('bloodcare.donor_details.donation_profile_help') }}</p></div></header>
                <dl class="bc-detail-list">
                    <div><dt>{{ __('bloodcare.donors.blood_group') }}</dt><dd><span class="bc-group-badge">{{ $donor->blood_group }}</span></dd></div>
                    <div><dt>{{ __('bloodcare.donor_details.donation_preference') }}</dt><dd>{{ __('bloodcare.donor_details.'.$donor->donation_type_preference) }}</dd></div>
                    <div><dt>{{ __('bloodcare.donors.last_donation') }}</dt><dd>{{ $donor->last_donation_date?->format('d M Y') ?? __('bloodcare.donors.not_recorded') }}</dd></div>
                    <div><dt>{{ __('bloodcare.donors.next_eligible') }}</dt><dd>{{ $donor->next_eligible_date?->format('d M Y') ?? __('bloodcare.donors.eligible_now') }}</dd></div>
                    <div class="bc-detail-span"><dt>{{ __('bloodcare.donors.notes') }}</dt><dd>{{ $donor->staff_notes ?: __('bloodcare.donors.not_recorded') }}</dd></div>
                </dl>
            </section>

            <section class="bc-panel bc-record-section">
                <header class="bc-record-section-heading"><span><i class="la la-calendar-times"></i></span><div><h2>{{ __('bloodcare.donor_details.current_deferral') }}</h2><p>{{ __('bloodcare.donor_details.current_deferral_help') }}</p></div></header>
                <dl class="bc-detail-list">
                    <div><dt>{{ __('bloodcare.donor_details.deferral_type') }}</dt><dd>{{ __('bloodcare.donor_details.deferral_'.$donor->deferral_type) }}</dd></div>
                    <div><dt>{{ __('bloodcare.donor_details.deferral_end') }}</dt><dd>{{ $donor->deferral_end_date?->format('d M Y') ?? __('bloodcare.donors.not_recorded') }}</dd></div>
                    <div class="bc-detail-span"><dt>{{ __('bloodcare.donor_details.deferral_reason') }}</dt><dd>{{ $donor->deferral_reason ?: __('bloodcare.donors.not_recorded') }}</dd></div>
                </dl>
            </section>
        </div>

            </div>

            <div class="bc-record-section-panel" id="bc-details-section-clinical" role="tabpanel" data-bc-section-panel="clinical" data-bc-section-hash="clinical-record">

        <section class="bc-panel bc-record-section">
            <header class="bc-record-section-heading"><span><i class="la la-heartbeat"></i></span><div><h2>{{ __('bloodcare.donor_details.latest_screening') }}</h2><p>{{ __('bloodcare.donor_details.latest_screening_help') }}</p></div></header>
            @if ($donor->latestScreening)
                @php $latest = $donor->latestScreening; @endphp
                <div class="bc-latest-screening-grid">
                <section class="bc-clinical-summary-card">
                    <h3><i class="la la-heartbeat"></i> {{ __('bloodcare.donor_details.physical_vitals') }}</h3>
                <dl class="bc-detail-list bc-detail-list-six">
                    <div><dt>{{ __('bloodcare.donor_details.screening_reference') }}</dt><dd>{{ $latest->reference }}</dd></div>
                    <div><dt>{{ __('bloodcare.donor_details.screened_at') }}</dt><dd>{{ $latest->screened_at?->format('d M Y, H:i') }}</dd></div>
                    <div><dt>{{ __('bloodcare.donor_details.screening_outcome') }}</dt><dd><span class="bc-status bc-status-{{ $latest->outcome }}">{{ __('bloodcare.donor_details.outcome_'.$latest->outcome) }}</span></dd></div>
                    <div><dt>{{ __('bloodcare.donor_details.weight_kg') }}</dt><dd>{{ $latest->weight_kg }} kg</dd></div>
                    <div><dt>{{ __('bloodcare.donor_details.hemoglobin') }}</dt><dd>{{ $latest->hemoglobin_level }} g/dL</dd></div>
                    <div><dt>{{ __('bloodcare.donor_details.blood_pressure') }}</dt><dd>{{ $latest->systolic_blood_pressure }}/{{ $latest->diastolic_blood_pressure }} mmHg</dd></div>
                    <div><dt>{{ __('bloodcare.donor_details.pulse_rate') }}</dt><dd>{{ $latest->pulse_rate }} bpm</dd></div>
                    <div><dt>{{ __('bloodcare.donor_details.temperature') }}</dt><dd>{{ $latest->body_temperature_celsius }} °C</dd></div>
                    <div><dt>{{ __('bloodcare.donor_details.verified_by') }}</dt><dd>{{ $latest->verifiedBy?->name ?? __('bloodcare.donors.not_recorded') }}</dd></div>
                    <div><dt>{{ __('bloodcare.donor_details.centre') }}</dt><dd>{{ $latest->centre?->name ?? __('bloodcare.donors.not_recorded') }}</dd></div>
                    <div><dt>{{ __('bloodcare.donor_details.appointment') }}</dt><dd>{{ $latest->appointment?->reference ?? __('bloodcare.donors.not_recorded') }}</dd></div>
                    <div><dt>{{ __('bloodcare.donor_details.next_screening_date') }}</dt><dd>{{ $latest->next_screening_date?->format('d M Y') ?? __('bloodcare.donors.not_recorded') }}</dd></div>
                </dl>
                </section>
                <section class="bc-clinical-summary-card">
                    <h3><i class="la la-notes-medical"></i> {{ __('bloodcare.donor_details.medical_regulatory_summary') }}</h3>
                    <dl class="bc-detail-list">
                        <div><dt>{{ __('bloodcare.donor_details.medication') }}</dt><dd>{{ $latest->medication_flag ? __('bloodcare.donor_details.reported_yes') : __('bloodcare.donor_details.reported_no') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.medication_details') }}</dt><dd>{{ $latest->current_medications ?: __('bloodcare.donors.not_recorded') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.recent_travel') }}</dt><dd>{{ $latest->recent_travel_flag ? __('bloodcare.donor_details.reported_yes') : __('bloodcare.donor_details.reported_no') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.recent_travel_details') }}</dt><dd>{{ $latest->recent_travel_details ?: __('bloodcare.donors.not_recorded') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.high_risk') }}</dt><dd>{{ $latest->high_risk_activity_flag ? __('bloodcare.donor_details.reported_yes') : __('bloodcare.donor_details.reported_no') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.high_risk_details') }}</dt><dd>{{ $latest->high_risk_activity_details ?: __('bloodcare.donors.not_recorded') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.deferral_type') }}</dt><dd>{{ __('bloodcare.donor_details.deferral_'.$latest->deferral_type) }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.deferral_end') }}</dt><dd>{{ $latest->deferral_end_date?->format('d M Y') ?? __('bloodcare.donors.not_recorded') }}</dd></div>
                        <div class="bc-detail-span"><dt>{{ __('bloodcare.donor_details.deferral_reason') }}</dt><dd>{{ $latest->deferral_reason ?: __('bloodcare.donors.not_recorded') }}</dd></div>
                        <div class="bc-detail-span"><dt>{{ __('bloodcare.donor_details.screening_notes') }}</dt><dd>{{ $latest->notes ?: __('bloodcare.donors.not_recorded') }}</dd></div>
                    </dl>
                </section>
                </div>
            @else
                <div class="bc-empty-inline"><i class="la la-notes-medical"></i><div><strong>{{ __('bloodcare.donor_details.no_screenings') }}</strong><p>{{ __('bloodcare.donor_details.no_screenings_help') }}</p></div></div>
            @endif
        </section>

        <details class="bc-panel bc-record-section bc-screening-entry" @if ($errors->any()) open @endif>
            <summary><span><i class="la la-plus-circle"></i></span><div><strong>{{ __('bloodcare.donor_details.record_new_screening') }}</strong><small>{{ __('bloodcare.donor_details.record_new_screening_help') }}</small></div><i class="la la-angle-down"></i></summary>
            <form method="post" action="{{ route('bloodcare.admin.donors.screenings.store', $donor) }}">
                @csrf
                @include('admin.donors.screening-fields', ['prefix' => 'details'])
                <footer class="bc-record-actions"><button class="btn bc-btn-primary" type="submit"><i class="la la-heartbeat"></i> {{ __('bloodcare.donor_details.save_screening') }}</button></footer>
            </form>
        </details>

            </div>

        <section class="bc-panel bc-record-section bc-history-section bc-record-section-panel" id="donation-history" role="tabpanel" data-bc-section-panel="donations" data-bc-section-hash="donation-history">
            <header class="bc-record-section-heading"><span><i class="la la-tint"></i></span><div><h2>{{ __('bloodcare.donor_details.donation_history') }}</h2><p>{{ __('bloodcare.donor_details.donation_history_help') }}</p></div></header>
            <form class="bc-history-filter" method="get" action="{{ route('bloodcare.admin.donors.show', $donor) }}#donation-history">
                <label><span>{{ __('bloodcare.donor_details.search') }}</span><input class="form-control" name="donation_search" value="{{ request('donation_search') }}" placeholder="{{ __('bloodcare.donor_details.donation_search_placeholder') }}"></label>
                <label><span>{{ __('bloodcare.donor_details.donation_type') }}</span><select class="form-select" name="donation_type"><option value="">{{ __('bloodcare.donor_details.all_types') }}</option>@foreach (['whole_blood', 'platelets', 'plasma'] as $value)<option value="{{ $value }}" @selected(request('donation_type') === $value)>{{ __('bloodcare.donor_details.'.$value) }}</option>@endforeach</select></label>
                <label><span>{{ __('bloodcare.donors.status') }}</span><select class="form-select" name="donation_status"><option value="">{{ __('bloodcare.donor_details.all_statuses') }}</option>@foreach (['accepted', 'screening', 'rejected'] as $value)<option value="{{ $value }}" @selected(request('donation_status') === $value)>{{ __('bloodcare.donations.'.$value) }}</option>@endforeach</select></label>
                <label><span>{{ __('bloodcare.donor_details.from_date') }}</span><input class="form-control" type="date" name="donation_from" value="{{ request('donation_from') }}"></label>
                <label><span>{{ __('bloodcare.donor_details.to_date') }}</span><input class="form-control" type="date" name="donation_to" value="{{ request('donation_to') }}"></label>
                <div class="bc-history-filter-action">
                    <button class="btn bc-btn-primary" type="submit"><i class="la la-search"></i> {{ __('bloodcare.donor_details.filter') }}</button>
                </div>
            </form>
            <div class="table-responsive"><table class="table bc-table bc-responsive-table bc-donation-history-table"><thead><tr><th>{{ __('bloodcare.donor_details.reference') }}</th><th>{{ __('bloodcare.donor_details.donation_date') }}</th><th>{{ __('bloodcare.donor_details.staff') }}</th><th>{{ __('bloodcare.donor_details.actions') }}</th></tr></thead><tbody>
                @forelse ($donations as $donation)<tr><td><strong>{{ $donation->reference }}</strong></td><td>{{ $donation->donation_date?->format('d M Y') ?? __('bloodcare.donor_details.no_value') }}</td><td>{{ $donation->recordedBy?->name ?? __('bloodcare.donor_details.no_value') }}</td><td><div class="bc-history-row-actions"><button class="btn bc-history-details-button" type="button" data-bc-history-details-open="bc-donation-history-modal-{{ $donation->id }}" aria-label="{{ __('bloodcare.donor_details.view_details') }}" title="{{ __('bloodcare.donor_details.view_details') }}"><i class="la la-eye" aria-hidden="true"></i></button><a class="btn bc-history-edit-button" href="{{ route('bloodcare.admin.donations.edit', $donation->reference) }}" aria-label="{{ __('bloodcare.donor_details.edit_record') }}" title="{{ __('bloodcare.donor_details.edit_record') }}"><i class="la la-pen" aria-hidden="true"></i></a><form method="post" action="{{ route('bloodcare.admin.donations.destroy', $donation->reference) }}" data-bc-history-delete data-confirm-message="{{ __('bloodcare.donor_details.confirm_delete_donation', ['reference' => $donation->reference]) }}">@csrf @method('DELETE')<button class="btn bc-history-delete-button" type="submit" aria-label="{{ __('bloodcare.donor_details.delete_record') }}" title="{{ __('bloodcare.donor_details.delete_record') }}"><i class="la la-trash" aria-hidden="true"></i></button></form></div></td></tr>@empty<tr><td colspan="4" class="bc-empty-state">{{ __('bloodcare.donor_details.no_donation_history') }}</td></tr>@endforelse
            </tbody></table></div>
            {{ $donations->links() }}
        </section>

        <section class="bc-panel bc-record-section bc-history-section bc-record-section-panel" id="screening-history" role="tabpanel" data-bc-section-panel="screenings" data-bc-section-hash="screening-history">
            <header class="bc-record-section-heading"><span><i class="la la-notes-medical"></i></span><div><h2>{{ __('bloodcare.donor_details.screening_history') }}</h2><p>{{ __('bloodcare.donor_details.screening_history_help') }}</p></div></header>
            <form class="bc-history-filter" method="get" action="{{ route('bloodcare.admin.donors.show', $donor) }}#screening-history">
                <label><span>{{ __('bloodcare.donor_details.search') }}</span><input class="form-control" name="screening_search" value="{{ request('screening_search') }}" placeholder="{{ __('bloodcare.donor_details.screening_search_placeholder') }}"></label>
                <label><span>{{ __('bloodcare.donor_details.screening_outcome') }}</span><select class="form-select" name="screening_outcome"><option value="">{{ __('bloodcare.donor_details.all_outcomes') }}</option>@foreach (['passed', 'pending', 'deferred', 'failed'] as $value)<option value="{{ $value }}" @selected(request('screening_outcome') === $value)>{{ __('bloodcare.donor_details.outcome_'.$value) }}</option>@endforeach</select></label>
                <label><span>{{ __('bloodcare.donor_details.from_date') }}</span><input class="form-control" type="date" name="screening_from" value="{{ request('screening_from') }}"></label>
                <label><span>{{ __('bloodcare.donor_details.to_date') }}</span><input class="form-control" type="date" name="screening_to" value="{{ request('screening_to') }}"></label>
                <div class="bc-history-filter-action">
                    <button class="btn bc-btn-primary" type="submit"><i class="la la-search"></i> {{ __('bloodcare.donor_details.filter') }}</button>
                </div>
            </form>
            <div class="table-responsive"><table class="table bc-table bc-responsive-table bc-screening-history-table"><thead><tr><th>{{ __('bloodcare.donor_details.reference') }}</th><th>{{ __('bloodcare.donor_details.screened_at') }}</th><th>{{ __('bloodcare.donor_details.verified_by') }}</th><th>{{ __('bloodcare.donor_details.actions') }}</th></tr></thead><tbody>
                @forelse ($screenings as $screening)<tr><td><strong>{{ $screening->reference }}</strong></td><td>{{ $screening->screened_at?->format('d M Y, H:i') ?? __('bloodcare.donor_details.no_value') }}</td><td>{{ $screening->verifiedBy?->name ?? __('bloodcare.donor_details.no_value') }}</td><td><div class="bc-history-row-actions"><button class="btn bc-history-details-button" type="button" data-bc-history-details-open="bc-screening-history-modal-{{ $screening->id }}" aria-label="{{ __('bloodcare.donor_details.view_details') }}" title="{{ __('bloodcare.donor_details.view_details') }}"><i class="la la-eye" aria-hidden="true"></i></button><a class="btn bc-history-edit-button" href="{{ route('bloodcare.admin.donors.screenings.edit', [$donor, $screening]) }}" aria-label="{{ __('bloodcare.donor_details.edit_record') }}" title="{{ __('bloodcare.donor_details.edit_record') }}"><i class="la la-pen" aria-hidden="true"></i></a><form method="post" action="{{ route('bloodcare.admin.donors.screenings.destroy', [$donor, $screening]) }}" data-bc-history-delete data-confirm-message="{{ __('bloodcare.donor_details.confirm_delete_screening', ['reference' => $screening->reference]) }}">@csrf @method('DELETE')<button class="btn bc-history-delete-button" type="submit" aria-label="{{ __('bloodcare.donor_details.delete_record') }}" title="{{ __('bloodcare.donor_details.delete_record') }}"><i class="la la-trash" aria-hidden="true"></i></button></form></div></td></tr>@empty<tr><td colspan="4" class="bc-empty-state">{{ __('bloodcare.donor_details.no_screening_history') }}</td></tr>@endforelse
            </tbody></table></div>
            {{ $screenings->links() }}
        </section>

        @include('admin.donors.history-detail-modals')
        </div>
    </div>
@endsection
