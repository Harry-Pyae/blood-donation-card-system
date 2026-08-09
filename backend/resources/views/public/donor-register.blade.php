@extends('layouts.public')

@section('title', __('bloodcare.public.registration.title'))
@section('meta_description', __('bloodcare.public.registration.meta'))

@section('content')
    <section class="page-hero">
        <div class="public-container page-hero-grid">
            <div>
                <p class="eyebrow">{{ __('bloodcare.public.registration.eyebrow') }}</p>
                <h1>{{ __('bloodcare.public.registration.hero_title') }}</h1>
                <p>{{ __('bloodcare.public.registration.hero_text') }}</p>
            </div>
            <div class="page-hero-icon"><x-bloodcare-icon name="user" :size="62" /></div>
        </div>
    </section>

    <section class="form-section">
        <div class="public-container form-shell">
            <form
                class="form-card public-registration-form"
                method="POST"
                action="{{ route('donor.register.store') }}"
                data-public-registration-form
                data-submitting-label="{{ __('bloodcare.public.registration.submitting') }}"
                aria-label="{{ __('bloodcare.public.registration.form_aria') }}"
            >
                @csrf

                <div class="form-progress" aria-label="{{ __('bloodcare.public.registration.progress_aria') }}">
                    <span class="active" data-registration-step="1">
                        <b>1</b>
                        {{ __('bloodcare.public.registration.step_personal') }}
                    </span>
                    <span data-registration-step="2">
                        <b>2</b>
                        {{ __('bloodcare.public.registration.step_identity') }}
                    </span>
                    <span data-registration-step="3">
                        <b>3</b>
                        {{ __('bloodcare.public.registration.step_contact') }}
                    </span>
                    <span data-registration-step="4">
                        <b>4</b>
                        {{ __('bloodcare.public.registration.step_health') }}
                    </span>
                </div>

                <p class="required-note">
                    <span aria-hidden="true">*</span>
                    {{ __('bloodcare.public.registration.required_note') }}
                </p>

                @if ($errors->any())
                    <div class="error-summary" role="alert" tabindex="-1" data-registration-error-summary>
                        <strong>{{ __('bloodcare.public.registration.error_title') }}</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section class="form-group-section" data-registration-section="1">
                    <div class="form-section-heading">
                        <span>1</span>
                        <div>
                            <h2>{{ __('bloodcare.public.registration.personal_title') }}</h2>
                            <p>{{ __('bloodcare.public.registration.personal_help') }}</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-field full">
                            <label for="full_name">{{ __('bloodcare.public.registration.full_name') }} <span class="required">*</span></label>
                            <input id="full_name" name="full_name" value="{{ old('full_name') }}" maxlength="120" autocomplete="name" required>
                        </div>
                        <div class="form-field registration-paired-field">
                            <label for="date_of_birth">{{ __('bloodcare.public.registration.date_of_birth') }} <span class="required">*</span></label>
                            <input
                                id="date_of_birth"
                                type="date"
                                name="date_of_birth"
                                data-bc-date-purpose="birth"
                                value="{{ old('date_of_birth') }}"
                                max="{{ $maxBirthDate }}"
                                aria-describedby="date-of-birth-help"
                                required
                            >
                            <small id="date-of-birth-help">{{ __('bloodcare.public.registration.date_help') }}</small>
                        </div>
                        <div class="form-field registration-paired-field">
                            <label for="gender">{{ __('bloodcare.public.registration.gender') }} <span class="required">*</span></label>
                            <select id="gender" name="gender" required>
                                <option value="">{{ __('bloodcare.public.registration.select_gender') }}</option>
                                <option value="female" @selected(old('gender') === 'female')>{{ __('bloodcare.public.registration.female') }}</option>
                                <option value="male" @selected(old('gender') === 'male')>{{ __('bloodcare.public.registration.male') }}</option>
                                <option value="other" @selected(old('gender') === 'other')>{{ __('bloodcare.public.registration.other_gender') }}</option>
                            </select>
                        </div>
                        <div class="form-field full">
                            <label for="blood_group">{{ __('bloodcare.public.registration.blood_group') }} <span class="required">*</span></label>
                            <select id="blood_group" name="blood_group" aria-describedby="blood-group-help" required>
                                <option value="unknown">{{ __('bloodcare.public.registration.unknown_group') }}</option>
                                @foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $group)
                                    <option value="{{ $group }}" @selected(old('blood_group') === $group)>{{ $group }}</option>
                                @endforeach
                            </select>
                            <small id="blood-group-help">{{ __('bloodcare.public.registration.blood_group_help') }}</small>
                        </div>
                    </div>
                </section>

                @php
                    $identityDocumentType = old('identity_document_type', 'nrc');
                    $selectedNrcState = (string) old('nrc_state', '');
                    $selectedNrcTownship = (string) old('nrc_township', '');
                    $nrcPageConfig = [
                        'townships' => $nrcTownships,
                        'labels' => [
                            'selectStateFirst' => __('bloodcare.public.registration.select_state_first'),
                            'selectTownship' => __('bloodcare.public.registration.select_township'),
                            'previewEmpty' => __('bloodcare.public.registration.nrc_preview_empty'),
                        ],
                    ];
                @endphp

                <section class="form-group-section identity-section" data-registration-section="2">
                    <div class="form-section-heading">
                        <span>2</span>
                        <div>
                            <h2>{{ __('bloodcare.public.registration.identity_title') }}</h2>
                            <p>{{ __('bloodcare.public.registration.identity_help') }}</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-field full">
                            <label for="identity_document_type">{{ __('bloodcare.public.registration.document_type') }} <span class="required">*</span></label>
                            <select id="identity_document_type" name="identity_document_type" data-identity-document-type required>
                                <option value="nrc" @selected($identityDocumentType === 'nrc')>{{ __('bloodcare.public.registration.document_nrc') }}</option>
                                <option value="passport" @selected($identityDocumentType === 'passport')>{{ __('bloodcare.public.registration.document_passport') }}</option>
                            </select>
                        </div>

                        <fieldset
                            class="nrc-card full"
                            data-nrc-fields
                            @if($identityDocumentType !== 'nrc') hidden @endif
                        >
                            <legend>{{ __('bloodcare.public.registration.nrc_title') }}</legend>
                            <p>{{ __('bloodcare.public.registration.nrc_help') }}</p>

                            <div class="nrc-input-grid">
                                <div class="form-field">
                                    <label for="nrc_state">{{ __('bloodcare.public.registration.nrc_state') }} <span class="required">*</span></label>
                                    <select
                                        id="nrc_state"
                                        name="nrc_state"
                                        data-nrc-state
                                        @if($identityDocumentType === 'nrc') required @else disabled @endif
                                    >
                                        <option value="">{{ __('bloodcare.public.registration.select_state') }}</option>
                                        @foreach ($nrcStates as $code => $state)
                                            <option value="{{ $code }}" @selected($selectedNrcState === (string) $code)>
                                                {{ $code }} — {{ app()->isLocale('my') ? $state['my'] : $state['en'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-field">
                                    <label for="nrc_township">{{ __('bloodcare.public.registration.nrc_township') }} <span class="required">*</span></label>
                                    <select
                                        id="nrc_township"
                                        name="nrc_township"
                                        data-nrc-township
                                        data-selected-value="{{ $selectedNrcTownship }}"
                                        @if($identityDocumentType === 'nrc') required @else disabled @endif
                                    >
                                        <option value="">
                                            {{ $selectedNrcState === ''
                                                ? __('bloodcare.public.registration.select_state_first')
                                                : __('bloodcare.public.registration.select_township') }}
                                        </option>
                                        @foreach (($nrcTownships[$selectedNrcState] ?? []) as $township)
                                            <option value="{{ $township['value'] }}" @selected($selectedNrcTownship === $township['value'])>
                                                {{ $township['display'] }} ({{ $township['myanmarCode'] }}) — {{ $township['myanmarName'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-field">
                                    <label for="nrc_type">{{ __('bloodcare.public.registration.nrc_type') }} <span class="required">*</span></label>
                                    <select
                                        id="nrc_type"
                                        name="nrc_type"
                                        data-nrc-type
                                        @if($identityDocumentType === 'nrc') required @else disabled @endif
                                    >
                                        <option value="">{{ __('bloodcare.public.registration.select_nrc_type') }}</option>
                                        @foreach ($nrcTypes as $code => $type)
                                            <option value="{{ $code }}" @selected(old('nrc_type') === $code)>
                                                ({{ $code }}) {{ app()->isLocale('my') ? $type['my'] : $type['en'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-field">
                                    <label for="nrc_serial">{{ __('bloodcare.public.registration.nrc_serial') }} <span class="required">*</span></label>
                                    <input
                                        id="nrc_serial"
                                        name="nrc_serial"
                                        value="{{ old('nrc_serial') }}"
                                        inputmode="numeric"
                                        maxlength="6"
                                        pattern="[0-9]{6}"
                                        placeholder="{{ __('bloodcare.public.registration.nrc_serial_placeholder') }}"
                                        aria-describedby="nrc-serial-help"
                                        data-nrc-serial
                                        @if($identityDocumentType === 'nrc') required @else disabled @endif
                                    >
                                    <small id="nrc-serial-help">{{ __('bloodcare.public.registration.nrc_serial_help') }}</small>
                                </div>
                            </div>

                            <div class="nrc-preview" aria-live="polite">
                                <span>{{ __('bloodcare.public.registration.nrc_preview') }}</span>
                                <strong data-nrc-preview>{{ __('bloodcare.public.registration.nrc_preview_empty') }}</strong>
                            </div>
                        </fieldset>

                        <div
                            class="form-field full passport-field"
                            data-passport-fields
                            @if($identityDocumentType !== 'passport') hidden @endif
                        >
                            <label for="passport_number">{{ __('bloodcare.public.registration.passport_number') }} <span class="required">*</span></label>
                            <input
                                id="passport_number"
                                name="passport_number"
                                value="{{ old('passport_number') }}"
                                minlength="5"
                                maxlength="20"
                                pattern="[A-Za-z0-9]+"
                                autocomplete="off"
                                placeholder="{{ __('bloodcare.public.registration.passport_placeholder') }}"
                                aria-describedby="passport-help"
                                @if($identityDocumentType === 'passport') required @else disabled @endif
                            >
                            <small id="passport-help">{{ __('bloodcare.public.registration.passport_help') }}</small>
                        </div>
                    </div>
                </section>

                <section class="form-group-section" data-registration-section="3">
                    <div class="form-section-heading">
                        <span>3</span>
                        <div>
                            <h2>{{ __('bloodcare.public.registration.contact_title') }}</h2>
                            <p>{{ __('bloodcare.public.registration.contact_help') }}</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-field">
                            @php
                                $publicPhoneValue = (string) old('phone_local', old('phone', ''));
                                $publicPhoneDigits = preg_replace('/[^0-9]+/', '', $publicPhoneValue) ?? '';
                                $publicPhoneLocal = str_starts_with($publicPhoneDigits, '95')
                                    ? substr($publicPhoneDigits, 2)
                                    : (str_starts_with($publicPhoneDigits, '0') ? substr($publicPhoneDigits, 1) : $publicPhoneDigits);
                            @endphp
                            <label for="phone_local">{{ __('bloodcare.public.registration.phone') }} <span class="required">*</span></label>
                            <input type="hidden" name="phone" value="{{ $publicPhoneLocal !== '' ? '+95 '.$publicPhoneLocal : '' }}" data-public-phone-full>
                            <span class="public-phone-prefix-field">
                                <strong>+95</strong>
                                <input id="phone_local" name="phone_local" value="{{ $publicPhoneLocal }}" minlength="7" maxlength="12" pattern="[1-9][0-9]{6,11}" inputmode="numeric" autocomplete="tel-national" required>
                            </span>
                            <small>{{ __('bloodcare.public.registration.phone_prefix_help') }}</small>
                        </div>
                        <div class="form-field">
                            <label for="email">{{ __('bloodcare.public.registration.email') }}</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="120" inputmode="email" autocomplete="email">
                        </div>
                        <div class="form-field full">
                            <label for="address">{{ __('bloodcare.public.registration.address') }} <span class="required">*</span></label>
                            <textarea id="address" name="address" maxlength="500" autocomplete="street-address" required>{{ old('address') }}</textarea>
                        </div>
                        <div class="form-field full">
                            <label for="emergency_contact">{{ __('bloodcare.public.registration.emergency_contact') }} <span class="required">*</span></label>
                            <input
                                id="emergency_contact"
                                name="emergency_contact"
                                value="{{ old('emergency_contact') }}"
                                maxlength="120"
                                placeholder="{{ __('bloodcare.public.registration.emergency_placeholder') }}"
                                required
                            >
                        </div>
                    </div>
                </section>

                <section class="form-group-section" data-registration-section="4">
                    <div class="form-section-heading">
                        <span>4</span>
                        <div>
                            <h2>{{ __('bloodcare.public.registration.health_title') }}</h2>
                            <p>{{ __('bloodcare.public.registration.health_help') }}</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-field">
                            <label for="previous_donation">{{ __('bloodcare.public.registration.donated_before') }} <span class="required">*</span></label>
                            <select id="previous_donation" name="previous_donation" required>
                                <option value="">{{ __('bloodcare.public.registration.select_answer') }}</option>
                                <option value="yes" @selected(old('previous_donation') === 'yes')>{{ __('bloodcare.public.registration.yes') }}</option>
                                <option value="no" @selected(old('previous_donation') === 'no')>{{ __('bloodcare.public.registration.no') }}</option>
                            </select>
                        </div>
                        <div class="form-field full">
                            <label for="health_notes">{{ __('bloodcare.public.registration.health_notes') }}</label>
                            <textarea
                                id="health_notes"
                                name="health_notes"
                                maxlength="1000"
                                aria-describedby="health-notes-help"
                                placeholder="{{ __('bloodcare.public.registration.health_placeholder') }}"
                                data-character-count-input
                            >{{ old('health_notes') }}</textarea>
                            <small id="health-notes-help" class="field-help-row">
                                <span>{{ __('bloodcare.public.registration.health_notes_help') }}</span>
                                <span data-character-count aria-live="polite"></span>
                            </small>
                        </div>
                        <div class="form-field full">
                            <div class="check-row">
                                <input id="consent" type="checkbox" name="consent" value="1" @checked(old('consent')) required>
                                <label for="consent">{{ __('bloodcare.public.registration.consent') }}</label>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="form-actions">
                    <button class="button button-primary" type="submit" data-registration-submit>
                        <span data-registration-submit-label>{{ __('bloodcare.public.registration.validate') }}</span>
                        <x-bloodcare-icon name="arrow" :size="21" />
                    </button>
                    <a class="button button-secondary" href="{{ route('home') }}">{{ __('bloodcare.public.registration.cancel') }}</a>
                </div>
            </form>

            <aside class="side-stack">
                <div class="side-card accent">
                    <x-bloodcare-icon name="shield" :size="30" />
                    <h2>{{ __('bloodcare.public.registration.protected_title') }}</h2>
                    <p>{{ __('bloodcare.public.registration.protected_text') }}</p>
                </div>
                <div class="side-card">
                    <x-bloodcare-icon name="check" :size="29" />
                    <h2>{{ __('bloodcare.public.registration.before_title') }}</h2>
                    <ul class="compact-list">
                        <li><x-bloodcare-icon name="check" :size="20" /> {{ __('bloodcare.public.registration.before_identity') }}</li>
                        <li><x-bloodcare-icon name="check" :size="20" /> {{ __('bloodcare.public.registration.before_phone') }}</li>
                        <li><x-bloodcare-icon name="check" :size="20" /> {{ __('bloodcare.public.registration.before_group') }}</li>
                    </ul>
                </div>
                <div class="side-card side-card-link">
                    <x-bloodcare-icon name="heart" :size="29" />
                    <h2>{{ __('bloodcare.public.registration.unsure_title') }}</h2>
                    <p>{{ __('bloodcare.public.registration.unsure_text') }}</p>
                    <a href="{{ route('eligibility') }}">
                        {{ __('bloodcare.public.registration.check_eligibility') }}
                        <x-bloodcare-icon name="arrow" :size="20" />
                    </a>
                </div>
            </aside>
        </div>
    </section>

    <script id="bc-public-nrc-config" type="application/json">{!! json_encode($nrcPageConfig, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endsection
