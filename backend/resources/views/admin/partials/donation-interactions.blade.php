@php
    $donationConfig = [
        ...$donationData,
        'locale' => app()->getLocale(),
        'csrfToken' => csrf_token(),
        'storeUrl' => route('bloodcare.admin.donations.store'),
        'updateUrlTemplate' => route('bloodcare.admin.donations.update', ['donation' => '__REFERENCE__']),
        'statusUrlTemplate' => route('bloodcare.admin.donations.status', ['donation' => '__REFERENCE__']),
        'labels' => [
            'accepted' => __('bloodcare.donations.accepted'),
            'screening' => __('bloodcare.donations.screening'),
            'rejected' => __('bloodcare.donations.rejected'),
            'passed' => __('bloodcare.donations.passed'),
            'pending' => __('bloodcare.donations.pending'),
            'failed' => __('bloodcare.donations.failed'),
            'showingRange' => __('bloodcare.donations.showing_range'),
            'noResults' => __('bloodcare.donations.no_results'),
            'actionsFor' => __('bloodcare.donations.open_actions'),
            'view' => __('bloodcare.donations.view'),
            'edit' => __('bloodcare.donations.edit'),
            'accept' => __('bloodcare.donations.accept'),
            'reject' => __('bloodcare.donations.reject'),
            'printReceipt' => __('bloodcare.donations.print_receipt'),
            'recordTitle' => __('bloodcare.donations.record_title'),
            'editTitle' => __('bloodcare.donations.edit_title'),
            'saveRecord' => __('bloodcare.donations.save_record'),
            'completeReview' => __('bloodcare.donations.complete_review'),
            'chooseDonorPlaceholder' => __('bloodcare.donations.choose_donor_placeholder'),
            'donationId' => __('bloodcare.donations.donation_id'),
            'appointmentReference' => __('bloodcare.donations.appointment_reference'),
            'screeningReference' => __('bloodcare.donations.medical_screening'),
            'noLinkedScreening' => __('bloodcare.donations.no_linked_screening'),
            'screeningUnavailable' => __('bloodcare.donations.screening_unavailable'),
            'donationType' => __('bloodcare.donations.donation_type'),
            'noLinkedAppointment' => __('bloodcare.donations.no_linked_appointment'),
            'appointmentUnavailable' => __('bloodcare.donations.appointment_unavailable'),
            'donationDate' => __('bloodcare.donations.donation_date'),
            'bloodGroup' => __('bloodcare.donations.blood_group'),
            'quantity' => __('bloodcare.donations.quantity'),
            'screeningResult' => __('bloodcare.donations.screening_result'),
            'bagUnit' => __('bloodcare.donations.bag_unit'),
            'expiryDate' => __('bloodcare.donations.expiry_date'),
            'location' => __('bloodcare.donations.location'),
            'status' => __('bloodcare.donations.status'),
            'staffMember' => __('bloodcare.donations.staff_member'),
            'notes' => __('bloodcare.donations.notes'),
            'inventoryUnit' => __('bloodcare.donations.inventory_unit'),
            'nextEligible' => __('bloodcare.donations.next_eligible'),
            'notApplicable' => __('bloodcare.donations.not_applicable'),
            'notRecorded' => __('bloodcare.donations.not_recorded'),
            'receiptTitle' => __('bloodcare.donations.receipt_title'),
            'receiptSubtitle' => __('bloodcare.donations.receipt_subtitle'),
            'confirmAccept' => __('bloodcare.donations.confirm_accept'),
            'confirmReject' => __('bloodcare.donations.confirm_reject'),
            'confirmReset' => __('bloodcare.donations.confirm_reset'),
            'savedMessage' => __('bloodcare.donations.saved_message'),
            'acceptedMessage' => __('bloodcare.donations.accepted_message'),
            'rejectedMessage' => __('bloodcare.donations.rejected_message'),
            'exportedMessage' => __('bloodcare.donations.exported_message'),
            'resetMessage' => __('bloodcare.donations.reset_message'),
            'donorNotEligible' => __('bloodcare.donations.donor_not_eligible'),
            'invalidDates' => __('bloodcare.donations.invalid_dates'),
            'invalidResult' => __('bloodcare.donations.invalid_result'),
            'duplicateDonation' => __('bloodcare.donations.duplicate_donation'),
            'duplicateUnit' => __('bloodcare.donations.duplicate_unit'),
            'noEligibleDonors' => __('bloodcare.donations.no_eligible_donors'),
            'csvColumns' => __('bloodcare.donations.csv_columns'),
        ],
    ];
@endphp

<script id="bc-donation-config" type="application/json">@json($donationConfig)</script>

<div class="bc-modal" id="bc-donation-editor" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.donations.close') }}"></button>
    <section class="bc-modal-dialog bc-modal-dialog-wide" role="dialog" aria-modal="true" aria-labelledby="bc-donation-editor-title">
        <header class="bc-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.donations.workflow_eyebrow') }}</p>
                <h2 id="bc-donation-editor-title">{{ __('bloodcare.donations.record_title') }}</h2>
                <p>{{ __('bloodcare.donations.form_help') }}</p>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.donations.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>

        <form id="bc-donation-form">
            <div class="bc-form-alert-error" id="bc-donation-form-error" tabindex="-1" hidden></div>

            <div class="bc-form-grid bc-form-grid-two">
                <label class="bc-field">
                    <span>{{ __('bloodcare.donations.choose_donor') }}</span>
                    <div class="bc-filter-dropdown bc-form-dropdown" data-bc-donor-select data-bc-select>
                        <i class="la la-user-check bc-filter-dropdown-icon" aria-hidden="true"></i>
                        <select class="visually-hidden" id="bc-donation-donor" name="donorId" required
                                tabindex="-1" aria-hidden="true">
                            <option value="">{{ __('bloodcare.donations.choose_donor_placeholder') }}</option>
                        </select>
                        <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox"
                                aria-expanded="false" aria-controls="bc-donation-donor-menu">
                            <span data-bc-select-label>{{ __('bloodcare.donations.choose_donor_placeholder') }}</span>
                            <i class="la la-angle-down" aria-hidden="true"></i>
                        </button>
                        <div class="bc-filter-dropdown-menu" id="bc-donation-donor-menu" role="listbox"
                             aria-label="{{ __('bloodcare.donations.choose_donor') }}" hidden>
                            <button type="button" role="option" data-value="" aria-selected="true">
                                <span>{{ __('bloodcare.donations.choose_donor_placeholder') }}</span>
                                <i class="la la-check" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donations.donation_id') }}</span>
                    <input class="form-control" id="bc-donation-id" name="donationId" type="text" readonly>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donations.appointment_reference') }}</span>
                    <select class="form-select" id="bc-donation-appointment" name="appointmentReference">
                        <option value="">{{ __('bloodcare.donations.no_linked_appointment') }}</option>
                    </select>
                    <small>{{ __('bloodcare.donations.appointment_help') }}</small>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donations.medical_screening') }}</span>
                    <select class="form-select" id="bc-donation-screening-reference" name="screeningReference">
                        <option value="">{{ __('bloodcare.donations.no_linked_screening') }}</option>
                    </select>
                    <small>{{ __('bloodcare.donations.medical_screening_help') }}</small>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donations.donation_date') }}</span>
                    <input class="form-control" id="bc-donation-date-field" name="donationDate" type="date"
                           max="{{ $donationData['today'] }}" required>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donations.blood_group') }}</span>
                    <input class="form-control" id="bc-donation-form-group" name="group" type="text" readonly>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donations.donation_type') }}</span>
                    <select class="form-select" id="bc-donation-type" name="donationType" required>
                        <option value="whole_blood">{{ __('bloodcare.donor_details.whole_blood') }}</option>
                        <option value="platelets">{{ __('bloodcare.donor_details.platelets') }}</option>
                        <option value="plasma">{{ __('bloodcare.donor_details.plasma') }}</option>
                    </select>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donations.quantity') }}</span>
                    <select class="form-select" id="bc-donation-quantity" name="quantity" required>
                        <option value="350">350 ml</option>
                        <option value="450" selected>450 ml</option>
                        <option value="500">500 ml</option>
                    </select>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donations.screening_result') }}</span>
                    <select class="form-select" id="bc-donation-screening" name="screeningResult" required>
                        <option value="Passed">{{ __('bloodcare.donations.passed') }}</option>
                        <option value="Pending">{{ __('bloodcare.donations.pending') }}</option>
                        <option value="Failed">{{ __('bloodcare.donations.failed') }}</option>
                    </select>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donations.status') }}</span>
                    <select class="form-select" id="bc-donation-status-field" name="status" required>
                        <option value="Accepted">{{ __('bloodcare.donations.accepted') }}</option>
                        <option value="Screening">{{ __('bloodcare.donations.screening') }}</option>
                        <option value="Rejected">{{ __('bloodcare.donations.rejected') }}</option>
                    </select>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donations.bag_unit') }}</span>
                    <input class="form-control" id="bc-donation-unit" name="bagUnit" type="text" required
                           pattern="BU-[0-9]{6}" placeholder="BU-026186">
                    <small>{{ __('bloodcare.donations.bag_unit_help') }}</small>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donations.expiry_date') }}</span>
                    <input class="form-control" id="bc-donation-expiry" name="expiryDate" type="date" required>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donations.location') }}</span>
                    <select class="form-select" id="bc-donation-location" name="location" required>
                        <option value="Cold room A">{{ __('bloodcare.donations.cold_room_a') }}</option>
                        <option value="Cold room B">{{ __('bloodcare.donations.cold_room_b') }}</option>
                        <option value="Cold room C">{{ __('bloodcare.donations.cold_room_c') }}</option>
                    </select>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donations.staff_member') }}</span>
                    <input class="form-control" id="bc-donation-staff" name="staff" type="text"
                           value="{{ $donationData['staffName'] }}" readonly>
                </label>
            </div>

            <label class="bc-field">
                <span>{{ __('bloodcare.donations.notes') }}</span>
                <textarea class="form-control" id="bc-donation-notes" name="notes" rows="3" maxlength="600"
                          placeholder="{{ __('bloodcare.donations.notes_placeholder') }}"></textarea>
            </label>

            <footer class="bc-modal-footer">
                <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.donations.cancel') }}</button>
                <button class="btn bc-btn-primary" id="bc-donation-submit" type="submit">
                    <i class="la la-tint"></i> <span>{{ __('bloodcare.donations.save_record') }}</span>
                </button>
            </footer>
        </form>
    </section>
</div>

<div class="bc-modal" id="bc-donation-details" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.donations.close') }}"></button>
    <section class="bc-modal-dialog bc-donation-receipt-dialog" role="dialog" aria-modal="true" aria-labelledby="bc-donation-details-title">
        <header class="bc-modal-header bc-donation-receipt-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.donations.details_eyebrow') }}</p>
                <h2 id="bc-donation-details-title">{{ __('bloodcare.donations.details_title') }}</h2>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.donations.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>
        <div class="bc-donation-details-content" id="bc-donation-details-content"></div>
        <footer class="bc-modal-footer bc-donation-receipt-actions">
            <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.donations.close') }}</button>
            <button class="btn bc-btn-primary" id="bc-donation-print" type="button">
                <i class="la la-print"></i> {{ __('bloodcare.donations.print_receipt') }}
            </button>
        </footer>
    </section>
</div>

<div class="bc-modal" id="bc-donation-data-source-modal" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.donations.close') }}"></button>
    <section class="bc-modal-dialog bc-modal-dialog-small" role="dialog" aria-modal="true" aria-labelledby="bc-donation-data-source-title">
        <header class="bc-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.donations.data_source_eyebrow') }}</p>
                <h2 id="bc-donation-data-source-title">{{ __('bloodcare.donations.data_source_title') }}</h2>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.donations.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>
        <div class="bc-data-source-copy">
            <span class="bc-data-source-icon"><i class="la la-link"></i></span>
            <div>
                <strong>{{ __('bloodcare.donations.browser_storage_title') }}</strong>
                <p>{{ __('bloodcare.donations.browser_storage_text') }}</p>
            </div>
        </div>
        <footer class="bc-modal-footer">
            <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.donations.close') }}</button>
            <button class="btn bc-btn-danger-outline" id="bc-reset-donations" type="button" hidden>
                <i class="la la-undo"></i> {{ __('bloodcare.donations.reset_sample') }}
            </button>
        </footer>
    </section>
</div>

<div class="bc-toast" id="bc-donation-toast" role="status" aria-live="polite" hidden>
    <i class="la la-check-circle"></i>
    <span></span>
</div>
