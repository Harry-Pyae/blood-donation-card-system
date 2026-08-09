@extends(backpack_view('blank'))

@php
    $editing = $donor !== null;
    $registrationMode = $editing ? 'edit' : ($registrationMode ?? 'new');
    $pageTitle = $editing
        ? __('bloodcare.donors.edit_title')
        : __($registrationMode === 'linked'
            ? 'bloodcare.donor_details.register_existing_donor'
            : 'bloodcare.donor_details.register_new_donor');
    $nrcStates = $nrcReference['nrcStates'] ?? [];
    $nrcTownships = $nrcReference['nrcTownships'] ?? [];
    $nrcTypes = $nrcReference['nrcTypes'] ?? [];
    $documentType = old('identityDocumentType', $donor?->identity_document_type ?? 'nrc');
    $phoneForForm = (string) old('phoneLocal', old('phone', $donor?->phone ?? ''));
    $phoneDigits = preg_replace('/[^0-9]+/', '', strtr($phoneForForm, [
        '၀' => '0', '၁' => '1', '၂' => '2', '၃' => '3', '၄' => '4',
        '၅' => '5', '၆' => '6', '၇' => '7', '၈' => '8', '၉' => '9',
    ])) ?? '';
    $phoneLocal = str_starts_with($phoneDigits, '95')
        ? substr($phoneDigits, 2)
        : (str_starts_with($phoneDigits, '0') ? substr($phoneDigits, 1) : $phoneDigits);
    $donorFormConfig = [
        'locale' => app()->getLocale(),
        'townships' => $nrcTownships,
        'labels' => [
            'selectTownship' => __('bloodcare.public.registration.select_township'),
            'selectStateFirst' => __('bloodcare.public.registration.select_state_first'),
        ],
    ];
    $initialFormSection = 'personal';
    $sectionErrorFields = [
        'identity' => ['identityDocumentType', 'nrcState', 'nrcTownship', 'nrcType', 'nrcSerial', 'passportNumber'],
        'donation' => ['group', 'donationTypePreference', 'status', 'eligibility', 'lastDonation', 'nextEligible', 'deferralType', 'deferralReason', 'deferralEndDate', 'notes'],
        'medical' => ['recordInitialScreening', 'screenedAt', 'nextScreeningDate', 'weightKg', 'hemoglobinLevel', 'systolicBloodPressure', 'diastolicBloodPressure', 'pulseRate', 'bodyTemperatureCelsius', 'medicationFlag', 'currentMedications', 'recentTravelFlag', 'recentTravelDetails', 'highRiskActivityFlag', 'highRiskActivityDetails', 'screeningOutcome', 'screeningDeferralType', 'screeningDeferralReason', 'screeningDeferralEndDate', 'appointmentReference', 'centreCode', 'screeningNotes'],
    ];
    foreach ($sectionErrorFields as $section => $fields) {
        if (collect($fields)->contains(fn ($field) => $errors->has($field))) {
            $initialFormSection = $section;
            break;
        }
    }
@endphp

@section('title', $pageTitle)

@push('before_styles')
    @include('admin.partials.favicon')
@endpush

@push('after_styles')
    <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}?v={{ filemtime(public_path('css/bloodcare-admin.css')) }}">
@endpush

@push('after_scripts')
    <script id="bc-donor-form-config" type="application/json">@json($donorFormConfig)</script>
    <script src="{{ asset('js/bloodcare-donor-form.js') }}?v={{ filemtime(public_path('js/bloodcare-donor-form.js')) }}" defer></script>
@endpush

@section('content')
    <div class="bc-admin-page bc-donor-record-page" data-bc-donor-form
         data-bc-registration-mode="{{ $registrationMode }}" data-bc-has-server-errors="{{ $errors->any() ? 'true' : 'false' }}">
        <header class="bc-page-heading bc-record-heading">
            <div class="bc-module-title">
                <span class="bc-module-icon"><i class="la {{ $editing ? 'la-user-edit' : 'la-user-plus' }}"></i></span>
                <div>
                    <p class="bc-eyebrow">{{ __('bloodcare.donors.workflow_eyebrow') }}</p>
                    <h1>{{ $pageTitle }}</h1>
                    <p>{{ $editing
                        ? __('bloodcare.donor_details.form_intro')
                        : __($registrationMode === 'linked'
                            ? 'bloodcare.donor_details.linked_registration_intro'
                            : 'bloodcare.donor_details.new_registration_intro') }}</p>
                </div>
            </div>
            <div class="bc-heading-tools">
                @include('admin.partials.utility-controls')
                <details class="bc-print-language-menu" data-bc-print-language-menu>
                    <summary class="btn bc-btn-outline">
                        <i class="la la-print"></i> {{ __('bloodcare.donor_details.print_form') }}
                        <i class="la la-angle-down"></i>
                    </summary>
                    <div class="bc-print-language-options">
                        <button type="button" data-bc-print-donor-locale="en">English</button>
                        <button type="button" data-bc-print-donor-locale="my">မြန်မာ</button>
                    </div>
                </details>
                <a class="btn bc-btn-outline" href="{{ $editing ? route('bloodcare.admin.donors.show', $donor) : route('bloodcare.admin.donors') }}">
                    <i class="la la-arrow-left"></i> {{ __('bloodcare.donor_details.back') }}
                </a>
            </div>
        </header>

        @if ($errors->any())
            <div class="bc-form-alert-error bc-record-alert" role="alert" tabindex="-1">
                <strong>{{ __('bloodcare.donor_details.fix_errors') }}</strong>
                <ul>
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form class="bc-record-form" method="post"
              action="{{ $editing ? route('bloodcare.admin.donors.update', $donor) : route('bloodcare.admin.donors.store') }}">
            @csrf
            @if ($editing) @method('PUT') @endif
            @unless ($editing)<input type="hidden" name="registrationMode" value="{{ $registrationMode }}">@endunless

            @if ($editing || $registrationMode === 'linked')
            <section class="bc-panel bc-record-section" data-bc-linked-user-section aria-labelledby="bc-user-link-title">
                <header class="bc-record-section-heading">
                    <span><i class="la la-user-circle"></i></span>
                    <div>
                        <h2 id="bc-user-link-title">{{ __('bloodcare.donor_details.user_section') }}</h2>
                        <p>{{ __('bloodcare.donor_details.user_section_help') }}</p>
                    </div>
                </header>
                <div class="bc-form-grid bc-form-grid-two">
                    <label class="bc-field bc-field-medium">
                        <span>{{ __('bloodcare.donor_details.linked_user') }}</span>
                        <select class="form-select" id="bc-donor-user-id" name="userId" data-bc-styled-select data-bc-linked-user-select
                                @required(!$editing && $registrationMode === 'linked')>
                            <option value="">{{ $editing ? __('bloodcare.donor_details.no_linked_user') : __('bloodcare.donor_details.choose_linked_user') }}</option>
                            @foreach ($publicUsers as $user)
                                <option value="{{ $user->id }}"
                                        data-user-name="{{ $user->name }}" data-user-email="{{ $user->email }}" data-user-phone="{{ $user->phone }}"
                                        @selected((string) old('userId', $donor?->user_id) === (string) $user->id)>
                                    {{ $user->name }} · {{ $user->email }}
                                </option>
                            @endforeach
                        </select>
                        @unless ($editing)<small>{{ __('bloodcare.donor_details.linked_prefill_help') }}</small>@endunless
                    </label>
                </div>
                <p class="bc-field-note"><i class="la la-lock"></i> {{ __('bloodcare.donor_details.user_boundary_help') }}</p>
            </section>
            @endif

            <div class="bc-record-section-scope" data-bc-section-scope data-bc-initial-section="{{ $initialFormSection }}"
                 @unless ($editing) data-bc-registration-flow @endunless>
                <nav class="bc-record-section-nav" data-bc-section-nav role="tablist" aria-label="{{ __('bloodcare.donor_details.section_navigation') }}">
                    <button class="is-active" type="button" role="tab" aria-selected="true" aria-controls="bc-donor-section-personal" data-bc-section-button data-bc-section-target="personal">
                        <i class="la la-address-card"></i><span>{{ __('bloodcare.donor_details.nav_personal') }}</span>
                    </button>
                    <button type="button" role="tab" aria-selected="false" aria-controls="bc-donor-section-identity" data-bc-section-button data-bc-section-target="identity" @disabled(!$editing)>
                        <i class="la la-id-card"></i><span>{{ __('bloodcare.donor_details.nav_identity') }}</span>
                    </button>
                    <button type="button" role="tab" aria-selected="false" aria-controls="bc-donor-section-donation" data-bc-section-button data-bc-section-target="donation" @disabled(!$editing)>
                        <i class="la la-tint"></i><span>{{ __('bloodcare.donor_details.nav_donation') }}</span>
                    </button>
                    @unless ($editing)
                        <button type="button" role="tab" aria-selected="false" aria-controls="bc-donor-section-medical" data-bc-section-button data-bc-section-target="medical" disabled>
                            <i class="la la-heartbeat"></i><span>{{ __('bloodcare.donor_details.nav_medical') }}</span>
                        </button>
                    @endunless
                </nav>

                <div class="bc-record-section-panel" id="bc-donor-section-personal" role="tabpanel" data-bc-section-panel="personal" data-bc-section-hash="personal-contact">

            <section class="bc-panel bc-record-section" aria-labelledby="bc-personal-title">
                <header class="bc-record-section-heading">
                    <span><i class="la la-address-card"></i></span>
                    <div><h2 id="bc-personal-title">{{ __('bloodcare.donor_details.personal_contact') }}</h2><p>{{ __('bloodcare.donor_details.personal_contact_help') }}</p></div>
                </header>
                <div class="bc-form-grid bc-form-grid-two">
                    <label class="bc-field"><span>{{ __('bloodcare.donors.full_name') }}</span><input class="form-control" name="name" value="{{ old('name', $donor?->full_name) }}" required maxlength="120"></label>
                    <label class="bc-field"><span>{{ __('bloodcare.donors.date_of_birth') }}</span><input class="form-control" name="dateOfBirth" type="date" value="{{ old('dateOfBirth', $donor?->date_of_birth?->toDateString()) }}" max="{{ $maxBirthDate }}" required><small>{{ __('bloodcare.donor_details.minimum_age_help') }}</small></label>
                    <label class="bc-field"><span>{{ __('bloodcare.donors.gender') }}</span><select class="form-select" name="gender" required>
                        @foreach (['Male' => __('bloodcare.donors.male'), 'Female' => __('bloodcare.donors.female'), 'Other' => __('bloodcare.donors.other')] as $value => $label)
                            <option value="{{ $value }}" @selected(strtolower((string) old('gender', $donor?->gender ?? 'Other')) === strtolower($value))>{{ $label }}</option>
                        @endforeach
                    </select></label>
                    <label class="bc-field"><span>{{ __('bloodcare.donors.phone') }}</span><input type="hidden" name="phone" value="{{ $phoneLocal !== '' ? '+95 '.$phoneLocal : '' }}" data-bc-phone-full><span class="bc-phone-prefix-field"><strong>+95</strong><input class="form-control" name="phoneLocal" type="tel" value="{{ $phoneLocal }}" inputmode="numeric" autocomplete="tel-national" pattern="[1-9][0-9]{6,11}" minlength="7" maxlength="12" required></span><small>{{ __('bloodcare.donor_details.phone_prefix_help') }}</small></label>
                    <label class="bc-field"><span>{{ __('bloodcare.donors.email') }}</span><input class="form-control" name="email" type="email" value="{{ old('email', $donor?->email) }}" maxlength="120"></label>
                    <label class="bc-field"><span>{{ __('bloodcare.donor_details.emergency_contact') }}</span><input class="form-control" name="emergencyContact" value="{{ old('emergencyContact', $donor?->emergency_contact) }}" maxlength="120"></label>
                </div>
                <label class="bc-field"><span>{{ __('bloodcare.donors.address') }}</span><textarea class="form-control" name="address" rows="3" maxlength="500">{{ old('address', $donor?->address) }}</textarea></label>
            </section>

                </div>

                <div class="bc-record-section-panel" id="bc-donor-section-identity" role="tabpanel" data-bc-section-panel="identity" data-bc-section-hash="identity">

            <section class="bc-panel bc-record-section" aria-labelledby="bc-identity-title">
                <header class="bc-record-section-heading">
                    <span><i class="la la-id-card"></i></span>
                    <div><h2 id="bc-identity-title">{{ __('bloodcare.public.registration.identity_title') }}</h2><p>{{ __('bloodcare.public.registration.identity_help') }}</p></div>
                </header>
                <label class="bc-field bc-field-medium"><span>{{ __('bloodcare.public.registration.document_type') }}</span><select class="form-select" id="bc-donor-document-type" name="identityDocumentType" required>
                    <option value="nrc" @selected($documentType === 'nrc')>{{ __('bloodcare.public.registration.document_nrc') }}</option>
                    <option value="passport" @selected($documentType === 'passport')>{{ __('bloodcare.public.registration.document_passport') }}</option>
                </select></label>

                <div class="bc-form-grid bc-form-grid-four" id="bc-donor-nrc-fields" @if ($documentType !== 'nrc') hidden @endif>
                    <label class="bc-field"><span>{{ __('bloodcare.public.registration.nrc_state') }}</span><select class="form-select" id="bc-donor-nrc-state" name="nrcState">
                        <option value="">{{ __('bloodcare.public.registration.select_state') }}</option>
                        @foreach ($nrcStates as $code => $state)
                            <option value="{{ $code }}" @selected((string) old('nrcState', $donor?->nrc_state) === (string) $code)>{{ $code }} — {{ app()->isLocale('my') ? $state['my'] : $state['en'] }}</option>
                        @endforeach
                    </select></label>
                    <label class="bc-field"><span>{{ __('bloodcare.public.registration.nrc_township') }}</span><select class="form-select" id="bc-donor-nrc-township" name="nrcTownship" data-selected="{{ old('nrcTownship', $donor?->nrc_township) }}"></select></label>
                    <label class="bc-field"><span>{{ __('bloodcare.public.registration.nrc_type') }}</span><select class="form-select" name="nrcType">
                        <option value="">{{ __('bloodcare.public.registration.select_nrc_type') }}</option>
                        @foreach ($nrcTypes as $code => $type)<option value="{{ $code }}" @selected(old('nrcType', $donor?->nrc_type) === $code)>({{ $code }}) {{ app()->isLocale('my') ? $type['my'] : $type['en'] }}</option>@endforeach
                    </select></label>
                    <label class="bc-field"><span>{{ __('bloodcare.public.registration.nrc_serial') }}</span><input class="form-control" name="nrcSerial" value="{{ old('nrcSerial', $donor?->nrc_serial) }}" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"></label>
                </div>

                <label class="bc-field bc-field-medium" id="bc-donor-passport-fields" @if ($documentType !== 'passport') hidden @endif>
                    <span>{{ __('bloodcare.public.registration.passport_number') }}</span>
                    <input class="form-control" name="passportNumber" value="{{ old('passportNumber', $donor?->passport_number) }}" minlength="5" maxlength="20" pattern="[A-Za-z0-9-]+">
                </label>
            </section>

                </div>

                <div class="bc-record-section-panel" id="bc-donor-section-donation" role="tabpanel" data-bc-section-panel="donation" data-bc-section-hash="donation-eligibility">

            <section class="bc-panel bc-record-section" aria-labelledby="bc-donation-profile-title">
                <header class="bc-record-section-heading">
                    <span><i class="la la-tint"></i></span>
                    <div><h2 id="bc-donation-profile-title">{{ __('bloodcare.donor_details.donation_profile') }}</h2><p>{{ __('bloodcare.donor_details.donation_profile_help') }}</p></div>
                </header>
                <div class="bc-form-grid bc-form-grid-three">
                    <label class="bc-field"><span>{{ __('bloodcare.donors.blood_group') }}</span>
                        @if ($editing)<input class="form-control" value="{{ $donor->blood_group }}" readonly><input type="hidden" name="group" value="{{ $donor->blood_group }}">
                        @else<select class="form-select" name="group" required>@foreach (['unknown', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $group)<option value="{{ $group }}" @selected(old('group', 'unknown') === $group)>{{ $group === 'unknown' ? __('bloodcare.donor_details.unknown_group') : $group }}</option>@endforeach</select>@endif
                    </label>
                    <label class="bc-field"><span>{{ __('bloodcare.donor_details.donation_preference') }}</span><select class="form-select" name="donationTypePreference" required>
                        @foreach (['whole_blood' => __('bloodcare.donor_details.whole_blood'), 'platelets' => __('bloodcare.donor_details.platelets'), 'plasma' => __('bloodcare.donor_details.plasma')] as $value => $label)<option value="{{ $value }}" @selected(old('donationTypePreference', $donor?->donation_type_preference ?? 'whole_blood') === $value)>{{ $label }}</option>@endforeach
                    </select></label>
                    <label class="bc-field"><span>{{ __('bloodcare.donors.status') }}</span><select class="form-select" name="status" required>@foreach (['Active', 'Pending', 'Inactive'] as $value)<option value="{{ $value }}" @selected(strtolower(old('status', $donor?->status ?? 'pending')) === strtolower($value))>{{ __('bloodcare.dashboard.status.'.strtolower($value)) }}</option>@endforeach</select></label>
                    <label class="bc-field"><span>{{ __('bloodcare.donors.eligibility') }}</span><select class="form-select" name="eligibility" required>@foreach (['Eligible', 'Review', 'Deferred'] as $value)<option value="{{ $value }}" @selected(strtolower(old('eligibility', $donor?->eligibility_status ?? 'review')) === strtolower($value))>{{ __('bloodcare.donors.'.strtolower($value)) }}</option>@endforeach</select></label>
                    <label class="bc-field"><span>{{ __('bloodcare.donors.last_donation') }}</span><input class="form-control" name="lastDonation" type="date" value="{{ old('lastDonation', $donor?->last_donation_date?->toDateString()) }}" max="{{ $today }}"><small>{{ __('bloodcare.donor_details.last_donation_optional_help') }}</small></label>
                    <label class="bc-field"><span>{{ __('bloodcare.donors.next_eligible') }}</span><input class="form-control" name="nextEligible" type="date" value="{{ old('nextEligible', $donor?->next_eligible_date?->toDateString()) }}"></label>
                </div>
            </section>

            <section class="bc-panel bc-record-section" aria-labelledby="bc-deferral-title">
                <header class="bc-record-section-heading"><span><i class="la la-calendar-times"></i></span><div><h2 id="bc-deferral-title">{{ __('bloodcare.donor_details.current_deferral') }}</h2><p>{{ __('bloodcare.donor_details.current_deferral_help') }}</p></div></header>
                <div class="bc-form-grid bc-form-grid-three">
                    <label class="bc-field"><span>{{ __('bloodcare.donor_details.deferral_type') }}</span><select class="form-select" id="bc-deferral-type" name="deferralType" required>@foreach (['none', 'temporary', 'permanent'] as $value)<option value="{{ $value }}" @selected(old('deferralType', $donor?->deferral_type ?? 'none') === $value)>{{ __('bloodcare.donor_details.deferral_'.$value) }}</option>@endforeach</select></label>
                    <label class="bc-field"><span>{{ __('bloodcare.donor_details.deferral_reason') }}</span><input class="form-control" id="bc-deferral-reason" name="deferralReason" value="{{ old('deferralReason', $donor?->deferral_reason) }}" maxlength="255"></label>
                    <label class="bc-field"><span>{{ __('bloodcare.donor_details.deferral_end') }}</span><input class="form-control" id="bc-deferral-end" name="deferralEndDate" type="date" value="{{ old('deferralEndDate', $donor?->deferral_end_date?->toDateString()) }}"></label>
                </div>
            </section>

            <section class="bc-panel bc-record-section">
                <header class="bc-record-section-heading"><span><i class="la la-clipboard"></i></span><div><h2>{{ __('bloodcare.donor_details.staff_notes') }}</h2><p>{{ __('bloodcare.donor_details.staff_notes_help') }}</p></div></header>
                <label class="bc-field"><span>{{ __('bloodcare.donors.notes') }}</span><textarea class="form-control" name="notes" rows="4" maxlength="1000">{{ old('notes', $donor?->staff_notes) }}</textarea></label>
            </section>

                </div>

            @unless ($editing)
                <div class="bc-record-section-panel" id="bc-donor-section-medical" role="tabpanel" data-bc-section-panel="medical" data-bc-section-hash="medical-screening">
                <section class="bc-panel bc-record-section" aria-labelledby="bc-initial-screening-title">
                    <header class="bc-record-section-heading"><span><i class="la la-heartbeat"></i></span><div><h2 id="bc-initial-screening-title">{{ __('bloodcare.donor_details.initial_screening') }}</h2><p>{{ __('bloodcare.donor_details.initial_screening_help') }}</p></div></header>
                    <label class="bc-check-row"><input id="bc-record-initial-screening" type="checkbox" name="recordInitialScreening" value="1" @checked(old('recordInitialScreening'))><span><strong>{{ __('bloodcare.donor_details.record_screening_now') }}</strong><small>{{ __('bloodcare.donor_details.record_screening_now_help') }}</small></span></label>
                    @include('admin.donors.screening-fields', ['prefix' => 'initial'])
                </section>
                </div>
            @else
                <input type="hidden" name="recordInitialScreening" value="0">
            @endunless

            </div>

            <footer class="bc-record-actions">
                <a class="btn bc-btn-outline" href="{{ $editing ? route('bloodcare.admin.donors.show', $donor) : route('bloodcare.admin.donors') }}">{{ __('bloodcare.donors.cancel') }}</a>
                @if ($editing)
                    <button class="btn bc-btn-primary" type="submit"><i class="la la-check"></i> {{ __('bloodcare.donors.save_donor') }}</button>
                @else
                    <button class="btn bc-btn-outline" type="button" data-bc-registration-back hidden>
                        <i class="la la-arrow-left"></i> {{ __('bloodcare.donor_details.previous_step') }}
                    </button>
                    <button class="btn bc-btn-primary" type="button" data-bc-registration-next>
                        {{ __('bloodcare.donor_details.next_step') }} <i class="la la-arrow-right"></i>
                    </button>
                    <button class="btn bc-btn-primary" type="submit" data-bc-registration-save hidden>
                        <i class="la la-check"></i> {{ __('bloodcare.donors.save_donor') }}
                    </button>
                @endif
            </footer>
        </form>

        @foreach (['en', 'my'] as $printLocale)
            @php
                $printSections = [
                    trans('bloodcare.donor_details.nav_personal', [], $printLocale) => [
                        ['field', trans('bloodcare.donors.full_name', [], $printLocale)],
                        ['field', trans('bloodcare.donors.date_of_birth', [], $printLocale)],
                        ['field', trans('bloodcare.donors.gender', [], $printLocale)],
                        ['field', trans('bloodcare.donors.phone', [], $printLocale)],
                        ['field', trans('bloodcare.donors.email', [], $printLocale)],
                        ['field', trans('bloodcare.donor_details.emergency_contact', [], $printLocale)],
                        ['field', trans('bloodcare.donors.address', [], $printLocale)],
                    ],
                    trans('bloodcare.donor_details.nav_identity', [], $printLocale) => [
                        ['identity-choice', trans('bloodcare.public.registration.document_type', [], $printLocale)],
                        ['field', trans('bloodcare.donor_details.print_identity_number', [], $printLocale)],
                    ],
                    trans('bloodcare.donor_details.nav_donation', [], $printLocale) => [
                        ['field', trans('bloodcare.donors.blood_group', [], $printLocale)],
                        ['field', trans('bloodcare.donor_details.donation_preference', [], $printLocale)],
                        ['field', trans('bloodcare.donors.last_donation', [], $printLocale)],
                        ['field', trans('bloodcare.donors.eligibility', [], $printLocale)],
                        ['field', trans('bloodcare.donor_details.deferral_type', [], $printLocale)],
                        ['field', trans('bloodcare.donor_details.deferral_reason', [], $printLocale)],
                    ],
                    trans('bloodcare.donor_details.nav_medical', [], $printLocale) => [
                        ['field', trans('bloodcare.donor_details.screened_at', [], $printLocale)],
                        ['field', trans('bloodcare.donor_details.weight_kg', [], $printLocale)],
                        ['field', trans('bloodcare.donor_details.hemoglobin', [], $printLocale)],
                        ['field', trans('bloodcare.donor_details.systolic_bp', [], $printLocale)],
                        ['field', trans('bloodcare.donor_details.diastolic_bp', [], $printLocale)],
                        ['field', trans('bloodcare.donor_details.pulse_rate', [], $printLocale)],
                        ['field', trans('bloodcare.donor_details.temperature', [], $printLocale)],
                        ['field', trans('bloodcare.donor_details.screening_outcome', [], $printLocale)],
                    ],
                ];
            @endphp
            <section class="bc-donor-print-sheet" data-bc-donor-print-sheet="{{ $printLocale }}" hidden lang="{{ $printLocale }}">
                <header class="bc-donor-print-brand">
                    <div class="bc-donor-print-brandline">
                        <span class="bc-donor-print-logo" aria-hidden="true">+</span>
                        <strong>BloodCare</strong>
                    </div>
                    <span>{{ trans('bloodcare.donor_details.print_title', [], $printLocale) }}</span>
                </header>
                @foreach ($printSections as $sectionTitle => $printFields)
                    <section class="bc-donor-print-section">
                        <h2>{{ $sectionTitle }}</h2>
                        <div class="bc-donor-print-grid">
                            @foreach ($printFields as [$fieldType, $fieldLabel])
                                <div>
                                    <span>{{ $fieldLabel }}</span>
                                    @if ($fieldType === 'identity-choice')
                                        <strong class="bc-donor-print-identity-choice">
                                            <span class="bc-donor-print-checkbox" aria-hidden="true"></span>
                                            {{ trans('bloodcare.donor_details.print_nrc', [], $printLocale) }}
                                            <span class="bc-donor-print-checkbox" aria-hidden="true"></span>
                                            {{ trans('bloodcare.donor_details.print_passport', [], $printLocale) }}
                                        </strong>
                                    @else
                                        <strong class="bc-donor-print-blank" aria-hidden="true"></strong>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
                <footer class="bc-donor-print-footer">
                    <span>{{ trans('bloodcare.donor_details.print_staff_signature', [], $printLocale) }} ____________________</span>
                    <span>{{ trans('bloodcare.donor_details.print_date', [], $printLocale) }} ____________________</span>
                </footer>
            </section>
        @endforeach
    </div>
@endsection
