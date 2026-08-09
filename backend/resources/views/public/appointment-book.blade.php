@extends('layouts.public')

@section('title', __('bloodcare.public.booking.title'))
@section('meta_description', __('bloodcare.public.booking.meta'))

@section('content')
    <section class="page-hero">
        <div class="public-container page-hero-grid">
            <div>
                <p class="eyebrow">{{ __('bloodcare.public.booking.eyebrow') }}</p>
                <h1>{{ __('bloodcare.public.booking.hero_title') }}</h1>
                <p>{{ __('bloodcare.public.booking.hero_text') }}</p>
            </div>
            <div class="page-hero-icon"><x-bloodcare-icon name="calendar" :size="62" /></div>
        </div>
    </section>

    <section class="form-section">
        <div class="public-container form-shell">
            <form
                class="form-card public-appointment-form"
                method="POST"
                action="{{ route('appointments.book.store') }}"
                data-public-appointment-form
                data-submitting-label="{{ __('bloodcare.public.booking.submitting') }}"
                aria-label="{{ __('bloodcare.public.booking.form_aria') }}"
            >
                @csrf

                <div class="booking-steps" aria-label="{{ __('bloodcare.public.booking.progress_aria') }}">
                    <div class="booking-step active" data-booking-step="1">
                        <span>1</span>
                        <strong>{{ __('bloodcare.public.booking.step_donor') }}</strong>
                    </div>
                    <div class="booking-step" data-booking-step="2">
                        <span>2</span>
                        <strong>{{ __('bloodcare.public.booking.step_slot') }}</strong>
                    </div>
                    <div class="booking-step" data-booking-step="3">
                        <span>3</span>
                        <strong>{{ __('bloodcare.public.booking.step_review') }}</strong>
                    </div>
                </div>

                <p class="required-note">
                    <span aria-hidden="true">*</span>
                    {{ __('bloodcare.public.booking.required_note') }}
                </p>

                @if ($errors->any())
                    <div class="error-summary" role="alert" tabindex="-1" data-appointment-error-summary>
                        <strong>{{ __('bloodcare.public.booking.error_title') }}</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section class="form-group-section" data-booking-section="1">
                    <div class="form-section-heading">
                        <span>1</span>
                        <div>
                            <h2>{{ __('bloodcare.public.booking.donor_title') }}</h2>
                            <p>{{ __('bloodcare.public.booking.donor_help') }}</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-field booking-paired-field">
                            <label for="donor_reference">{{ __('bloodcare.public.booking.donor_reference') }} <span class="required">*</span></label>
                            <input
                                id="donor_reference"
                                name="donor_reference"
                                value="{{ old('donor_reference') }}"
                                maxlength="40"
                                autocomplete="off"
                                placeholder="{{ __('bloodcare.public.booking.donor_placeholder') }}"
                                required
                            >
                            <small>{{ __('bloodcare.public.booking.donor_reference_help') }}</small>
                        </div>
                        <div class="form-field booking-paired-field">
                            <label for="appointment_phone">{{ __('bloodcare.public.booking.phone') }} <span class="required">*</span></label>
                            <input
                                id="appointment_phone"
                                name="phone"
                                value="{{ old('phone') }}"
                                maxlength="30"
                                inputmode="tel"
                                autocomplete="tel"
                                placeholder="{{ __('bloodcare.public.booking.phone_placeholder') }}"
                                required
                            >
                            <small>{{ __('bloodcare.public.booking.phone_help') }}</small>
                        </div>
                    </div>

                    <p class="form-inline-note">
                        {{ __('bloodcare.public.booking.no_reference') }}
                        <a href="{{ route('donor.register') }}">{{ __('bloodcare.public.booking.register_first') }}</a>
                    </p>
                </section>

                <section class="form-group-section" data-booking-section="2">
                    <div class="form-section-heading">
                        <span>2</span>
                        <div>
                            <h2>{{ __('bloodcare.public.booking.slot_title') }}</h2>
                            <p>{{ __('bloodcare.public.booking.slot_help') }}</p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-field full">
                            <label for="centre">{{ __('bloodcare.public.booking.centre') }} <span class="required">*</span></label>
                            <select id="centre" name="centre" required data-public-centre-select>
                                <option value="">{{ __('bloodcare.public.booking.choose_centre') }}</option>
                                @foreach ($centres as $centre)
                                    <option value="{{ $centre->name }}" @selected(old('centre') === $centre->name)>
                                        {{ $centre->name }} · {{ $centre->township }}
                                    </option>
                                @endforeach
                                <option value="__request__" @selected(old('centre') === '__request__')>{{ __('bloodcare.public.booking.request_location') }}</option>
                            </select>
                            <small>{{ __('bloodcare.public.booking.centre_help') }}</small>
                        </div>

                        <div
                            class="form-field full public-location-request"
                            data-location-request-fields
                            @if(old('centre') !== '__request__') hidden @endif
                        >
                            <div class="location-request-note">
                                <x-bloodcare-icon name="map" :size="25" />
                                <div>
                                    <strong>{{ __('bloodcare.public.booking.location_title') }}</strong>
                                    <p>{{ __('bloodcare.public.booking.location_help') }}</p>
                                </div>
                            </div>
                            <div class="form-grid">
                                <div class="form-field booking-paired-field">
                                    <label for="requested_region">{{ __('bloodcare.public.booking.region') }} <span class="required">*</span></label>
                                    <input
                                        id="requested_region"
                                        name="requested_region"
                                        value="{{ old('requested_region') }}"
                                        maxlength="120"
                                        placeholder="{{ __('bloodcare.public.booking.region_placeholder') }}"
                                        data-location-request-input
                                        @if(old('centre') !== '__request__') disabled @endif
                                    >
                                </div>
                                <div class="form-field booking-paired-field">
                                    <label for="requested_township">{{ __('bloodcare.public.booking.township') }} <span class="required">*</span></label>
                                    <input
                                        id="requested_township"
                                        name="requested_township"
                                        value="{{ old('requested_township') }}"
                                        maxlength="120"
                                        placeholder="{{ __('bloodcare.public.booking.township_placeholder') }}"
                                        data-location-request-input
                                        @if(old('centre') !== '__request__') disabled @endif
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="form-field booking-paired-field" data-appointment-slot-field>
                            <label for="appointment_date">{{ __('bloodcare.public.booking.date') }} <span class="required">*</span></label>
                            <input
                                id="appointment_date"
                                type="date"
                                name="appointment_date"
                                min="{{ now()->toDateString() }}"
                                max="{{ now()->addMonths(3)->toDateString() }}"
                                value="{{ old('appointment_date') }}"
                                required
                            >
                            <small>{{ __('bloodcare.public.booking.date_help') }}</small>
                        </div>
                        <div class="form-field booking-paired-field" data-appointment-slot-field>
                            <label for="appointment_time">{{ __('bloodcare.public.booking.time') }} <span class="required">*</span></label>
                            <select id="appointment_time" name="appointment_time" required>
                                <option value="">{{ __('bloodcare.public.booking.choose_time') }}</option>
                                @foreach (['09:00', '10:30', '13:00', '14:30'] as $time)
                                    <option value="{{ $time }}" @selected(old('appointment_time') === $time)>{{ $time }}</option>
                                @endforeach
                            </select>
                            <small>{{ __('bloodcare.public.booking.time_help') }}</small>
                        </div>
                    </div>
                </section>

                <section class="form-group-section" data-booking-section="3">
                    <div class="form-section-heading">
                        <span>3</span>
                        <div>
                            <h2>{{ __('bloodcare.public.booking.review_title') }}</h2>
                            <p>{{ __('bloodcare.public.booking.review_help') }}</p>
                        </div>
                    </div>

                    <div class="form-field full">
                        <label for="appointment_notes">{{ __('bloodcare.public.booking.notes') }}</label>
                        <textarea
                            id="appointment_notes"
                            name="notes"
                            maxlength="500"
                            placeholder="{{ __('bloodcare.public.booking.notes_placeholder') }}"
                            data-appointment-notes
                        >{{ old('notes') }}</textarea>
                        <small class="field-help-row">
                            <span>{{ __('bloodcare.public.booking.notes_help') }}</span>
                            <span data-appointment-notes-count aria-live="polite"></span>
                        </small>
                    </div>

                    <div class="appointment-review" aria-live="polite" data-appointment-review>
                        <h3>{{ __('bloodcare.public.booking.summary_title') }}</h3>
                        <dl>
                            <div>
                                <dt>{{ __('bloodcare.public.booking.summary_centre') }}</dt>
                                <dd data-review-centre>{{ __('bloodcare.public.booking.not_selected') }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('bloodcare.public.booking.summary_date') }}</dt>
                                <dd data-review-date>{{ __('bloodcare.public.booking.not_selected') }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('bloodcare.public.booking.summary_time') }}</dt>
                                <dd data-review-time>{{ __('bloodcare.public.booking.not_selected') }}</dd>
                            </div>
                        </dl>
                        <p data-review-location hidden></p>
                    </div>

                    <div class="check-row appointment-acknowledgement">
                        <input
                            id="booking_acknowledgement"
                            type="checkbox"
                            name="booking_acknowledgement"
                            value="1"
                            @checked(old('booking_acknowledgement'))
                            required
                        >
                        <label for="booking_acknowledgement">{{ __('bloodcare.public.booking.acknowledgement') }}</label>
                    </div>
                </section>

                <div class="form-actions">
                    <button class="button button-primary" type="submit" data-public-appointment-submit>
                        <span data-public-appointment-submit-label>{{ __('bloodcare.public.booking.review_button') }}</span>
                        <x-bloodcare-icon name="arrow" :size="21" />
                    </button>
                    <a class="button button-secondary" href="{{ route('appointments.check') }}">
                        {{ __('bloodcare.public.booking.check_existing') }}
                    </a>
                </div>
            </form>

            <aside class="side-stack">
                <div class="side-card accent">
                    <x-bloodcare-icon name="clock" :size="31" />
                    <h2>{{ __('bloodcare.public.booking.duration_title') }}</h2>
                    <p>{{ __('bloodcare.public.booking.duration_text') }}</p>
                </div>
                <div class="side-card">
                    <x-bloodcare-icon name="heart" :size="30" />
                    <h2>{{ __('bloodcare.public.booking.prepare_title') }}</h2>
                    <ul class="compact-list">
                        <li><x-bloodcare-icon name="check" :size="21" /> {{ __('bloodcare.public.booking.prepare_meal') }}</li>
                        <li><x-bloodcare-icon name="check" :size="21" /> {{ __('bloodcare.public.booking.prepare_water') }}</li>
                        <li><x-bloodcare-icon name="check" :size="21" /> {{ __('bloodcare.public.booking.prepare_identity') }}</li>
                    </ul>
                </div>
                <div class="side-card side-card-link">
                    <x-bloodcare-icon name="shield" :size="30" />
                    <h2>{{ __('bloodcare.public.booking.screening_title') }}</h2>
                    <p>{{ __('bloodcare.public.booking.screening_text') }}</p>
                    <a href="{{ route('eligibility') }}">
                        {{ __('bloodcare.public.booking.eligibility_link') }}
                        <x-bloodcare-icon name="arrow" :size="20" />
                    </a>
                </div>
            </aside>
        </div>
    </section>

    @php
        $appointmentPageConfig = [
            'locale' => app()->isLocale('my') ? 'my-MM' : 'en-GB',
            'centres' => $centres->map(fn ($centre) => [
                'name' => $centre->name,
                'region' => $centre->region,
                'township' => $centre->township,
                'address' => $centre->address,
                'hours' => $centre->opening_hours,
                'active' => $centre->is_active,
            ])->values(),
            'labels' => [
                'chooseCentre' => __('bloodcare.public.booking.choose_centre'),
                'requestLocation' => __('bloodcare.public.booking.request_location'),
                'sendLocationRequest' => __('bloodcare.public.booking.send_location_request'),
                'reviewAppointment' => __('bloodcare.public.booking.review_button'),
                'notSelected' => __('bloodcare.public.booking.not_selected'),
                'locationRequest' => __('bloodcare.public.booking.location_request_summary'),
                'locationSeparator' => __('bloodcare.public.booking.location_separator'),
                'completePreviousTitle' => __('bloodcare.public.booking.complete_previous_title'),
                'completePreviousMessage' => __('bloodcare.public.booking.complete_previous_message'),
            ],
        ];
    @endphp
    <script id="bc-public-appointment-config" type="application/json">{!! json_encode($appointmentPageConfig, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endsection
