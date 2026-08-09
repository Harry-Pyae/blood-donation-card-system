@php
    $initialScreening = ($prefix ?? '') === 'initial';
    $fieldIdPrefix = 'bc-'.($prefix ?? 'screening').'-screening';
    $screeningRecord = $screening ?? null;
@endphp
<div class="bc-screening-fields" @if ($initialScreening) id="bc-initial-screening-fields" hidden @endif>
    <section class="bc-clinical-field-group" aria-labelledby="bc-screening-vitals-title">
        <header>
            <span><i class="la la-heartbeat"></i></span>
            <div><h3 id="bc-screening-vitals-title">{{ __('bloodcare.donor_details.physical_vitals') }}</h3><p>{{ __('bloodcare.donor_details.physical_vitals_help') }}</p></div>
        </header>
        <div class="bc-form-grid bc-form-grid-three bc-vitals-grid">
            <div class="bc-field bc-vital-field bc-weight-field">
                <label for="{{ $fieldIdPrefix }}-weight">{{ __('bloodcare.donor_details.weight_kg') }}</label>
                <input class="form-control" id="{{ $fieldIdPrefix }}-weight" name="weightKg" type="number" step="0.01" min="25" max="300" value="{{ old('weightKg', $screeningRecord?->weight_kg) }}" placeholder="58.50" aria-describedby="{{ $fieldIdPrefix }}-weight-help" required>
                <small class="bc-vital-guidance" id="{{ $fieldIdPrefix }}-weight-help"><i class="la la-info-circle"></i> {{ __('bloodcare.donor_details.weight_guidance') }}</small>

                <div class="bc-weight-converter" data-bc-weight-converter>
                    <label for="{{ $fieldIdPrefix }}-pounds"><i class="la la-exchange-alt"></i> {{ __('bloodcare.donor_details.weight_converter') }}</label>
                    <div class="bc-weight-converter-controls">
                        <div class="bc-weight-pound-input">
                            <input class="form-control" id="{{ $fieldIdPrefix }}-pounds" type="number" step="0.1" min="55.1" max="661.4" inputmode="decimal" placeholder="{{ __('bloodcare.donor_details.weight_pounds_placeholder') }}" data-bc-weight-pounds aria-describedby="{{ $fieldIdPrefix }}-conversion-help">
                            <span>lb</span>
                        </div>
                        <button class="btn bc-weight-converter-button" type="button" data-bc-weight-apply disabled>
                            {{ __('bloodcare.donor_details.use_converted_kg') }}
                        </button>
                    </div>
                    <output class="bc-weight-converter-result" for="{{ $fieldIdPrefix }}-pounds" data-bc-weight-result aria-live="polite">{{ __('bloodcare.donor_details.conversion_waiting') }}</output>
                    <small id="{{ $fieldIdPrefix }}-conversion-help"><i class="la la-save"></i> {{ __('bloodcare.donor_details.conversion_save_notice') }}</small>
                </div>
            </div>

            <div class="bc-field bc-vital-field">
                <label for="{{ $fieldIdPrefix }}-hemoglobin">{{ __('bloodcare.donor_details.hemoglobin') }}</label>
                <input class="form-control" id="{{ $fieldIdPrefix }}-hemoglobin" name="hemoglobinLevel" type="number" step="0.01" min="3" max="25" value="{{ old('hemoglobinLevel', $screeningRecord?->hemoglobin_level) }}" placeholder="13.00" aria-describedby="{{ $fieldIdPrefix }}-hemoglobin-help" required>
                <small class="bc-vital-guidance" id="{{ $fieldIdPrefix }}-hemoglobin-help"><i class="la la-info-circle"></i> {{ __('bloodcare.donor_details.hemoglobin_guidance') }}</small>
            </div>

            <div class="bc-field bc-vital-field">
                <label for="{{ $fieldIdPrefix }}-temperature">{{ __('bloodcare.donor_details.temperature') }}</label>
                <input class="form-control" id="{{ $fieldIdPrefix }}-temperature" name="bodyTemperatureCelsius" type="number" step="0.01" min="30" max="45" value="{{ old('bodyTemperatureCelsius', $screeningRecord?->body_temperature_celsius) }}" placeholder="36.80" aria-describedby="{{ $fieldIdPrefix }}-temperature-help" required>
                <small class="bc-vital-guidance" id="{{ $fieldIdPrefix }}-temperature-help"><i class="la la-info-circle"></i> {{ __('bloodcare.donor_details.temperature_guidance') }}</small>
            </div>

            <div class="bc-field bc-vital-field">
                <label for="{{ $fieldIdPrefix }}-systolic">{{ __('bloodcare.donor_details.systolic_bp') }}</label>
                <input class="form-control" id="{{ $fieldIdPrefix }}-systolic" name="systolicBloodPressure" type="number" min="50" max="250" value="{{ old('systolicBloodPressure', $screeningRecord?->systolic_blood_pressure) }}" placeholder="120" aria-describedby="{{ $fieldIdPrefix }}-systolic-help" required>
                <small class="bc-vital-guidance" id="{{ $fieldIdPrefix }}-systolic-help"><i class="la la-info-circle"></i> {{ __('bloodcare.donor_details.systolic_guidance') }}</small>
            </div>

            <div class="bc-field bc-vital-field">
                <label for="{{ $fieldIdPrefix }}-diastolic">{{ __('bloodcare.donor_details.diastolic_bp') }}</label>
                <input class="form-control" id="{{ $fieldIdPrefix }}-diastolic" name="diastolicBloodPressure" type="number" min="30" max="150" value="{{ old('diastolicBloodPressure', $screeningRecord?->diastolic_blood_pressure) }}" placeholder="80" aria-describedby="{{ $fieldIdPrefix }}-diastolic-help" required>
                <small class="bc-vital-guidance" id="{{ $fieldIdPrefix }}-diastolic-help"><i class="la la-info-circle"></i> {{ __('bloodcare.donor_details.diastolic_guidance') }}</small>
            </div>

            <div class="bc-field bc-vital-field">
                <label for="{{ $fieldIdPrefix }}-pulse">{{ __('bloodcare.donor_details.pulse_rate') }}</label>
                <input class="form-control" id="{{ $fieldIdPrefix }}-pulse" name="pulseRate" type="number" min="30" max="220" value="{{ old('pulseRate', $screeningRecord?->pulse_rate) }}" placeholder="72" aria-describedby="{{ $fieldIdPrefix }}-pulse-help" required>
                <small class="bc-vital-guidance" id="{{ $fieldIdPrefix }}-pulse-help"><i class="la la-info-circle"></i> {{ __('bloodcare.donor_details.pulse_guidance') }}</small>
            </div>
        </div>
    </section>

    <section class="bc-clinical-field-group" aria-labelledby="bc-screening-medical-title">
        <header>
            <span><i class="la la-notes-medical"></i></span>
            <div><h3 id="bc-screening-medical-title">{{ __('bloodcare.donor_details.medical_history') }}</h3><p>{{ __('bloodcare.donor_details.medical_history_help') }}</p></div>
        </header>
        <div class="bc-risk-grid">
            @foreach ([
                ['medicationFlag', 'currentMedications', 'medication', 'medication_details', 'medication_flag', 'current_medications'],
                ['recentTravelFlag', 'recentTravelDetails', 'recent_travel', 'recent_travel_details', 'recent_travel_flag', 'recent_travel_details'],
                ['highRiskActivityFlag', 'highRiskActivityDetails', 'high_risk', 'high_risk_details', 'high_risk_activity_flag', 'high_risk_activity_details'],
            ] as [$flag, $details, $label, $detailLabel, $flagColumn, $detailsColumn])
                <div class="bc-risk-card" data-bc-risk-card>
                    <label class="bc-check-row"><input type="checkbox" name="{{ $flag }}" value="1" @checked(old($flag, (bool) data_get($screeningRecord, $flagColumn)))><span><strong>{{ __('bloodcare.donor_details.'.$label) }}</strong><small>{{ __('bloodcare.donor_details.'.$label.'_help') }}</small></span></label>
                    <label class="bc-field" data-bc-risk-details hidden><span>{{ __('bloodcare.donor_details.'.$detailLabel) }}</span><textarea class="form-control" name="{{ $details }}" rows="2" maxlength="2000">{{ old($details, data_get($screeningRecord, $detailsColumn)) }}</textarea></label>
                </div>
            @endforeach
        </div>
    </section>

    <section class="bc-clinical-field-group" aria-labelledby="bc-screening-operation-title">
        <header>
            <span><i class="la la-user-check"></i></span>
            <div><h3 id="bc-screening-operation-title">{{ __('bloodcare.donor_details.screening_operation') }}</h3><p>{{ __('bloodcare.donor_details.screening_operation_help') }}</p></div>
        </header>
        <div class="bc-form-grid bc-form-grid-three">
        <label class="bc-field"><span>{{ __('bloodcare.donor_details.screened_at') }}</span><input class="form-control" name="screenedAt" type="datetime-local" value="{{ old('screenedAt', $screeningRecord?->screened_at?->format('Y-m-d\TH:i') ?? $nowLocal ?? now()->format('Y-m-d\TH:i')) }}" required></label>
        <label class="bc-field"><span>{{ __('bloodcare.donor_details.next_screening_date') }}</span><input class="form-control" name="nextScreeningDate" type="date" value="{{ old('nextScreeningDate', $screeningRecord?->next_screening_date?->toDateString()) }}" @if (! $screeningRecord) min="{{ $today ?? now()->toDateString() }}" @endif></label>
        <label class="bc-field"><span>{{ __('bloodcare.donor_details.verified_by') }}</span><input class="form-control" value="{{ backpack_user()?->name }}" readonly></label>
        <label class="bc-field"><span>{{ __('bloodcare.donor_details.centre') }}</span><select class="form-select" name="centreCode"><option value="">{{ __('bloodcare.donor_details.no_centre') }}</option>@foreach ($centres as $centre)<option value="{{ $centre->code }}" @selected(old('centreCode', $screeningRecord?->centre?->code) === $centre->code)>{{ $centre->name }} · {{ $centre->township }}</option>@endforeach</select></label>
        <label class="bc-field"><span>{{ __('bloodcare.donor_details.appointment') }}</span><select class="form-select" name="appointmentReference"><option value="">{{ __('bloodcare.donor_details.no_appointment') }}</option>@foreach (($appointments ?? collect()) as $appointment)<option value="{{ $appointment->reference }}" @selected(old('appointmentReference', $screeningRecord?->appointment?->reference) === $appointment->reference)>{{ $appointment->reference }} · {{ $appointment->appointment_date?->format('d M Y') }}</option>@endforeach</select></label>
        </div>
    </section>

    <section class="bc-clinical-field-group" aria-labelledby="bc-screening-decision-title">
        <header>
            <span><i class="la la-gavel"></i></span>
            <div><h3 id="bc-screening-decision-title">{{ __('bloodcare.donor_details.regulatory_decision') }}</h3><p>{{ __('bloodcare.donor_details.regulatory_decision_help') }}</p></div>
        </header>
        <div class="bc-form-grid bc-form-grid-four bc-screening-deferral">
        <label class="bc-field"><span>{{ __('bloodcare.donor_details.screening_outcome') }}</span><select class="form-select" id="bc-screening-outcome" name="screeningOutcome" required>@foreach (['Passed', 'Pending', 'Deferred', 'Failed'] as $value)<option value="{{ $value }}" @selected(old('screeningOutcome', ucfirst($screeningRecord?->outcome ?? 'pending')) === $value)>{{ __('bloodcare.donor_details.outcome_'.strtolower($value)) }}</option>@endforeach</select></label>
        <label class="bc-field"><span>{{ __('bloodcare.donor_details.deferral_type') }}</span><select class="form-select" id="bc-screening-deferral-type" name="screeningDeferralType" required>@foreach (['none', 'temporary', 'permanent'] as $value)<option value="{{ $value }}" @selected(old('screeningDeferralType', $screeningRecord?->deferral_type ?? 'none') === $value)>{{ __('bloodcare.donor_details.deferral_'.$value) }}</option>@endforeach</select></label>
        <label class="bc-field"><span>{{ __('bloodcare.donor_details.deferral_reason') }}</span><input class="form-control" id="bc-screening-deferral-reason" name="screeningDeferralReason" value="{{ old('screeningDeferralReason', $screeningRecord?->deferral_reason) }}" maxlength="255"></label>
        <label class="bc-field"><span>{{ __('bloodcare.donor_details.deferral_end') }}</span><input class="form-control" id="bc-screening-deferral-end" name="screeningDeferralEndDate" type="date" value="{{ old('screeningDeferralEndDate', $screeningRecord?->deferral_end_date?->toDateString()) }}"></label>
        </div>
        <label class="bc-field"><span>{{ __('bloodcare.donor_details.screening_notes') }}</span><textarea class="form-control" name="screeningNotes" rows="3" maxlength="3000">{{ old('screeningNotes', $screeningRecord?->notes) }}</textarea></label>
        <p class="bc-clinical-note"><i class="la la-user-md"></i> {{ __('bloodcare.donor_details.clinical_decision_note') }}</p>
    </section>
</div>
