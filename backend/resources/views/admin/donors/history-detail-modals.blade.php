@foreach ($donations as $donation)
    <div class="bc-modal bc-donor-history-modal" id="bc-donation-history-modal-{{ $donation->id }}" data-bc-history-modal hidden>
        <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.donor_details.close_details') }}"></button>
        <section class="bc-modal-dialog bc-donor-history-dialog" role="dialog" aria-modal="true" aria-labelledby="bc-donation-history-title-{{ $donation->id }}" tabindex="-1">
            <header class="bc-modal-header">
                <div>
                    <p class="bc-eyebrow">{{ __('bloodcare.donor_details.donation_history') }}</p>
                    <h2 id="bc-donation-history-title-{{ $donation->id }}">{{ __('bloodcare.donor_details.donation_record_details') }}</h2>
                    <p>{{ $donation->reference }} · {{ $donation->donation_date?->format('d M Y') }}</p>
                </div>
                <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.donor_details.close_details') }}"><i class="la la-times"></i></button>
            </header>

            <div class="bc-donor-history-content">
                <section class="bc-history-modal-group">
                    <h3><i class="la la-file-medical-alt"></i> {{ __('bloodcare.donor_details.record_context') }}</h3>
                    <dl class="bc-history-modal-grid">
                        <div><dt>{{ __('bloodcare.donor_details.reference') }}</dt><dd>{{ $donation->reference }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.donation_date') }}</dt><dd>{{ $donation->donation_date?->format('d M Y') ?? __('bloodcare.donor_details.no_value') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.donation_type') }}</dt><dd>{{ __('bloodcare.donor_details.'.$donation->donation_type) }}</dd></div>
                        <div><dt>{{ __('bloodcare.donors.status') }}</dt><dd><span class="bc-status bc-status-{{ $donation->status }}">{{ __('bloodcare.donations.'.$donation->status) }}</span></dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.staff') }}</dt><dd>{{ $donation->recordedBy?->name ?? __('bloodcare.donor_details.no_value') }}</dd></div>
                    </dl>
                </section>

                <section class="bc-history-modal-group">
                    <h3><i class="la la-tint"></i> {{ __('bloodcare.donor_details.collection_details') }}</h3>
                    <dl class="bc-history-modal-grid">
                        <div><dt>{{ __('bloodcare.donor_details.quantity_ml') }}</dt><dd>{{ $donation->quantity_ml }} ml</dd></div>
                        <div><dt>{{ __('bloodcare.donors.blood_group') }}</dt><dd><span class="bc-group-badge">{{ $donation->blood_group }}</span></dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.bag_unit') }}</dt><dd>{{ $donation->bag_unit_number ?: __('bloodcare.donor_details.no_value') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.expires_at') }}</dt><dd>{{ $donation->expires_at?->format('d M Y') ?? __('bloodcare.donor_details.no_value') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.storage_location') }}</dt><dd>{{ $donation->storage_location ?: __('bloodcare.donor_details.no_value') }}</dd></div>
                    </dl>
                </section>

                <section class="bc-history-modal-group">
                    <h3><i class="la la-link"></i> {{ __('bloodcare.donor_details.linked_records') }}</h3>
                    <dl class="bc-history-modal-grid">
                        <div><dt>{{ __('bloodcare.donor_details.centre') }}</dt><dd>{{ $donation->centre?->name ?? $donation->appointment?->centre_name ?? __('bloodcare.donor_details.no_value') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.appointment') }}</dt><dd>{{ $donation->appointment?->reference ?? __('bloodcare.donor_details.no_value') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.linked_screening') }}</dt><dd>{{ $donation->screening?->reference ?? __('bloodcare.donor_details.no_value') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.screening_result') }}</dt><dd>{{ __('bloodcare.donations.'.$donation->screening_result) }}</dd></div>
                        <div class="bc-history-modal-wide"><dt>{{ __('bloodcare.donor_details.screening_notes') }}</dt><dd>{{ $donation->screening_notes ?: __('bloodcare.donor_details.no_value') }}</dd></div>
                    </dl>
                </section>
            </div>

            <footer class="bc-modal-footer">
                <span class="bc-history-read-only"><i class="la la-lock"></i> {{ __('bloodcare.donor_details.history_read_only') }}</span>
                <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.donor_details.close_details') }}</button>
            </footer>
        </section>
    </div>
@endforeach

@foreach ($screenings as $screening)
    <div class="bc-modal bc-donor-history-modal" id="bc-screening-history-modal-{{ $screening->id }}" data-bc-history-modal hidden>
        <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.donor_details.close_details') }}"></button>
        <section class="bc-modal-dialog bc-donor-history-dialog" role="dialog" aria-modal="true" aria-labelledby="bc-screening-history-title-{{ $screening->id }}" tabindex="-1">
            <header class="bc-modal-header">
                <div>
                    <p class="bc-eyebrow">{{ __('bloodcare.donor_details.screening_history') }}</p>
                    <h2 id="bc-screening-history-title-{{ $screening->id }}">{{ __('bloodcare.donor_details.screening_record_details') }}</h2>
                    <p>{{ $screening->reference }} · {{ $screening->screened_at?->format('d M Y, H:i') }}</p>
                </div>
                <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.donor_details.close_details') }}"><i class="la la-times"></i></button>
            </header>

            <div class="bc-donor-history-content">
                <section class="bc-history-modal-group">
                    <h3><i class="la la-user-check"></i> {{ __('bloodcare.donor_details.screening_context') }}</h3>
                    <dl class="bc-history-modal-grid">
                        <div><dt>{{ __('bloodcare.donor_details.reference') }}</dt><dd>{{ $screening->reference }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.screened_at') }}</dt><dd>{{ $screening->screened_at?->format('d M Y, H:i') ?? __('bloodcare.donor_details.no_value') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.next_screening_date') }}</dt><dd>{{ $screening->next_screening_date?->format('d M Y') ?? __('bloodcare.donor_details.no_value') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.verified_by') }}</dt><dd>{{ $screening->verifiedBy?->name ?? __('bloodcare.donor_details.no_value') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.centre') }}</dt><dd>{{ $screening->centre?->name ?? __('bloodcare.donor_details.no_value') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.appointment') }}</dt><dd>{{ $screening->appointment?->reference ?? __('bloodcare.donor_details.no_value') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.linked_donation') }}</dt><dd>{{ $screening->donation?->reference ?? __('bloodcare.donor_details.no_value') }}</dd></div>
                    </dl>
                </section>

                <section class="bc-history-modal-group">
                    <h3><i class="la la-heartbeat"></i> {{ __('bloodcare.donor_details.physical_vitals') }}</h3>
                    <dl class="bc-history-modal-grid bc-history-vitals-grid">
                        <div><dt>{{ __('bloodcare.donor_details.weight_kg') }}</dt><dd>{{ $screening->weight_kg }} kg</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.hemoglobin') }}</dt><dd>{{ $screening->hemoglobin_level }} g/dL</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.systolic_bp') }}</dt><dd>{{ $screening->systolic_blood_pressure }} mmHg</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.diastolic_bp') }}</dt><dd>{{ $screening->diastolic_blood_pressure }} mmHg</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.pulse_rate') }}</dt><dd>{{ $screening->pulse_rate }} bpm</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.temperature') }}</dt><dd>{{ $screening->body_temperature_celsius }} °C</dd></div>
                    </dl>
                </section>

                <section class="bc-history-modal-group">
                    <h3><i class="la la-notes-medical"></i> {{ __('bloodcare.donor_details.medical_history') }}</h3>
                    <dl class="bc-history-modal-grid">
                        <div><dt>{{ __('bloodcare.donor_details.medication') }}</dt><dd>{{ $screening->medication_flag ? __('bloodcare.donor_details.reported_yes') : __('bloodcare.donor_details.reported_no') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.medication_details') }}</dt><dd>{{ $screening->current_medications ?: __('bloodcare.donor_details.no_value') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.recent_travel') }}</dt><dd>{{ $screening->recent_travel_flag ? __('bloodcare.donor_details.reported_yes') : __('bloodcare.donor_details.reported_no') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.recent_travel_details') }}</dt><dd>{{ $screening->recent_travel_details ?: __('bloodcare.donor_details.no_value') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.high_risk') }}</dt><dd>{{ $screening->high_risk_activity_flag ? __('bloodcare.donor_details.reported_yes') : __('bloodcare.donor_details.reported_no') }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.high_risk_details') }}</dt><dd>{{ $screening->high_risk_activity_details ?: __('bloodcare.donor_details.no_value') }}</dd></div>
                    </dl>
                </section>

                <section class="bc-history-modal-group">
                    <h3><i class="la la-gavel"></i> {{ __('bloodcare.donor_details.regulatory_decision') }}</h3>
                    <dl class="bc-history-modal-grid">
                        <div><dt>{{ __('bloodcare.donor_details.screening_outcome') }}</dt><dd><span class="bc-status bc-status-{{ $screening->outcome }}">{{ __('bloodcare.donor_details.outcome_'.$screening->outcome) }}</span></dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.deferral_type') }}</dt><dd>{{ __('bloodcare.donor_details.deferral_'.$screening->deferral_type) }}</dd></div>
                        <div><dt>{{ __('bloodcare.donor_details.deferral_end') }}</dt><dd>{{ $screening->deferral_end_date?->format('d M Y') ?? __('bloodcare.donor_details.no_value') }}</dd></div>
                        <div class="bc-history-modal-wide"><dt>{{ __('bloodcare.donor_details.deferral_reason') }}</dt><dd>{{ $screening->deferral_reason ?: __('bloodcare.donor_details.no_value') }}</dd></div>
                        <div class="bc-history-modal-wide"><dt>{{ __('bloodcare.donor_details.screening_notes') }}</dt><dd>{{ $screening->notes ?: __('bloodcare.donor_details.no_value') }}</dd></div>
                    </dl>
                </section>
            </div>

            <footer class="bc-modal-footer">
                <span class="bc-history-read-only"><i class="la la-lock"></i> {{ __('bloodcare.donor_details.history_read_only') }}</span>
                <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.donor_details.close_details') }}</button>
            </footer>
        </section>
    </div>
@endforeach
