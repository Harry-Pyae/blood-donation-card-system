@php
    $appointmentConfig = [
        ...$appointmentData,
        'locale' => app()->getLocale(),
        'csrfToken' => csrf_token(),
        'storeUrl' => route('bloodcare.admin.appointments.store'),
        'updateUrlTemplate' => route('bloodcare.admin.appointments.update', ['appointment' => '__REFERENCE__']),
        'statusUrlTemplate' => route('bloodcare.admin.appointments.status', ['appointment' => '__REFERENCE__']),
        'centreStoreUrl' => route('bloodcare.admin.centres.store'),
        'centreUpdateUrlTemplate' => route('bloodcare.admin.centres.update', ['centre' => '__REFERENCE__']),
        'centreStatusUrlTemplate' => route('bloodcare.admin.centres.status', ['centre' => '__REFERENCE__']),
        'labels' => [
            'pending' => __('bloodcare.appointments.pending'),
            'confirmed' => __('bloodcare.appointments.confirmed'),
            'checked-in' => __('bloodcare.appointments.checked_in'),
            'completed' => __('bloodcare.appointments.completed'),
            'cancelled' => __('bloodcare.appointments.cancelled'),
            'no-show' => __('bloodcare.appointments.no_show'),
            'donation' => __('bloodcare.appointments.donation'),
            'eligibility-review' => __('bloodcare.appointments.eligibility_review'),
            'consultation' => __('bloodcare.appointments.consultation'),
            'staff' => __('bloodcare.appointments.staff'),
            'public-booking' => __('bloodcare.appointments.public_booking'),
            'showingRange' => __('bloodcare.appointments.showing_range'),
            'noResults' => __('bloodcare.appointments.no_results'),
            'actionsFor' => __('bloodcare.appointments.open_actions'),
            'view' => __('bloodcare.appointments.view'),
            'edit' => __('bloodcare.appointments.edit'),
            'confirm' => __('bloodcare.appointments.confirm'),
            'checkIn' => __('bloodcare.appointments.check_in'),
            'complete' => __('bloodcare.appointments.complete'),
            'markNoShow' => __('bloodcare.appointments.mark_no_show'),
            'cancelAppointment' => __('bloodcare.appointments.cancel_appointment'),
            'reopen' => __('bloodcare.appointments.reopen'),
            'recordDonation' => __('bloodcare.appointments.record_donation'),
            'donationCreated' => __('bloodcare.appointments.donation_created'),
            'createTitle' => __('bloodcare.appointments.create_title'),
            'editTitle' => __('bloodcare.appointments.edit_title'),
            'saveAppointment' => __('bloodcare.appointments.save_appointment'),
            'saveChanges' => __('bloodcare.appointments.save_changes'),
            'close' => __('bloodcare.appointments.close'),
            'chooseDonorPlaceholder' => __('bloodcare.appointments.choose_donor_placeholder'),
            'appointmentReference' => __('bloodcare.appointments.appointment_reference'),
            'appointmentDate' => __('bloodcare.appointments.appointment_date'),
            'appointmentTime' => __('bloodcare.appointments.appointment_time'),
            'purpose' => __('bloodcare.appointments.purpose'),
            'centre' => __('bloodcare.appointments.centre'),
            'status' => __('bloodcare.appointments.status'),
            'staffMember' => __('bloodcare.appointments.staff_member'),
            'source' => __('bloodcare.appointments.source'),
            'notes' => __('bloodcare.appointments.notes'),
            'donorId' => __('bloodcare.appointments.donor_id'),
            'bloodGroup' => __('bloodcare.appointments.blood_group'),
            'phone' => __('bloodcare.appointments.phone'),
            'eligibility' => __('bloodcare.appointments.eligibility'),
            'eligible' => __('bloodcare.appointments.eligible'),
            'notEligible' => __('bloodcare.appointments.not_eligible'),
            'notRecorded' => __('bloodcare.appointments.not_recorded'),
            'calendarEmpty' => __('bloodcare.appointments.calendar_empty'),
            'moreAppointments' => __('bloodcare.appointments.more_appointments'),
            'confirmBooking' => __('bloodcare.appointments.confirm_booking'),
            'confirmCheckIn' => __('bloodcare.appointments.confirm_check_in'),
            'confirmComplete' => __('bloodcare.appointments.confirm_complete'),
            'confirmNoShow' => __('bloodcare.appointments.confirm_no_show'),
            'confirmCancel' => __('bloodcare.appointments.confirm_cancel'),
            'confirmReopen' => __('bloodcare.appointments.confirm_reopen'),
            'confirmReset' => __('bloodcare.appointments.confirm_reset'),
            'savedMessage' => __('bloodcare.appointments.saved_message'),
            'confirmedMessage' => __('bloodcare.appointments.confirmed_message'),
            'checkedInMessage' => __('bloodcare.appointments.checked_in_message'),
            'completedMessage' => __('bloodcare.appointments.completed_message'),
            'noShowMessage' => __('bloodcare.appointments.no_show_message'),
            'cancelledMessage' => __('bloodcare.appointments.cancelled_message'),
            'reopenedMessage' => __('bloodcare.appointments.reopened_message'),
            'handoffMessage' => __('bloodcare.appointments.handoff_message'),
            'resetMessage' => __('bloodcare.appointments.reset_message'),
            'inactiveDonor' => __('bloodcare.appointments.inactive_donor'),
            'donorNotEligible' => __('bloodcare.appointments.donor_not_eligible'),
            'pastDate' => __('bloodcare.appointments.past_date'),
            'duplicateReference' => __('bloodcare.appointments.duplicate_reference'),
            'slotConflict' => __('bloodcare.appointments.slot_conflict'),
            'donorConflict' => __('bloodcare.appointments.donor_conflict'),
            'noDonors' => __('bloodcare.appointments.no_donors'),
            'notReadyForDonation' => __('bloodcare.appointments.not_ready_for_donation'),
            'alreadyConverted' => __('bloodcare.appointments.already_converted'),
            'manageCentres' => __('bloodcare.appointments.manage_centres'),
            'addCentre' => __('bloodcare.appointments.add_centre'),
            'editCentre' => __('bloodcare.appointments.edit_centre'),
            'saveCentre' => __('bloodcare.appointments.save_centre'),
            'updateCentre' => __('bloodcare.appointments.update_centre'),
            'cancelEdit' => __('bloodcare.appointments.cancel_edit'),
            'active' => __('bloodcare.appointments.centre_active'),
            'inactive' => __('bloodcare.appointments.centre_inactive'),
            'editCentreAction' => __('bloodcare.appointments.edit_centre_action'),
            'activateCentre' => __('bloodcare.appointments.activate_centre'),
            'deactivateCentre' => __('bloodcare.appointments.deactivate_centre'),
            'centreSaved' => __('bloodcare.appointments.centre_saved'),
            'centreActivated' => __('bloodcare.appointments.centre_activated'),
            'centreDeactivated' => __('bloodcare.appointments.centre_deactivated'),
            'duplicateCentre' => __('bloodcare.appointments.duplicate_centre'),
            'inactiveCentre' => __('bloodcare.appointments.inactive_centre'),
            'confirmCentreStatus' => __('bloodcare.appointments.confirm_centre_status'),
            'noCentres' => __('bloodcare.appointments.no_centres'),
        ],
    ];
@endphp

<script id="bc-appointment-config" type="application/json">@json($appointmentConfig)</script>

<div class="bc-modal" id="bc-appointment-editor" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.appointments.close') }}"></button>
    <section class="bc-modal-dialog bc-modal-dialog-wide" role="dialog" aria-modal="true" aria-labelledby="bc-appointment-editor-title">
        <header class="bc-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.appointments.workflow_eyebrow') }}</p>
                <h2 id="bc-appointment-editor-title">{{ __('bloodcare.appointments.create_title') }}</h2>
                <p>{{ __('bloodcare.appointments.form_help') }}</p>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.appointments.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>

        <form id="bc-appointment-form">
            <div class="bc-form-alert-error" id="bc-appointment-form-error" tabindex="-1" hidden></div>

            <div class="bc-form-grid bc-form-grid-two">
                <label class="bc-field">
                    <span>{{ __('bloodcare.appointments.choose_donor') }}</span>
                    <div class="bc-filter-dropdown bc-form-dropdown" data-bc-appointment-donor-select data-bc-select>
                        <i class="la la-user bc-filter-dropdown-icon" aria-hidden="true"></i>
                        <select class="visually-hidden" id="bc-appointment-donor" name="donorId" required
                                tabindex="-1" aria-hidden="true">
                            <option value="">{{ __('bloodcare.appointments.choose_donor_placeholder') }}</option>
                        </select>
                        <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox"
                                aria-expanded="false" aria-controls="bc-appointment-donor-menu">
                            <span data-bc-select-label>{{ __('bloodcare.appointments.choose_donor_placeholder') }}</span>
                            <i class="la la-angle-down" aria-hidden="true"></i>
                        </button>
                        <div class="bc-filter-dropdown-menu" id="bc-appointment-donor-menu" role="listbox"
                             aria-label="{{ __('bloodcare.appointments.choose_donor') }}" hidden>
                            <button type="button" role="option" data-value="" aria-selected="true">
                                <span>{{ __('bloodcare.appointments.choose_donor_placeholder') }}</span>
                                <i class="la la-check" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.appointments.appointment_reference') }}</span>
                    <input class="form-control" id="bc-appointment-reference" name="reference" type="text" readonly>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.appointments.appointment_date') }}</span>
                    <input class="form-control" id="bc-appointment-date-field" name="date" type="date"
                           min="{{ $appointmentData['today'] }}" required>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.appointments.appointment_time') }}</span>
                    <input class="form-control" id="bc-appointment-time" name="time" type="time"
                           min="08:00" max="16:30" step="1800" required>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.appointments.purpose') }}</span>
                    <select class="form-select" id="bc-appointment-purpose" name="purpose" required>
                        <option value="Donation">{{ __('bloodcare.appointments.donation') }}</option>
                        <option value="Eligibility review">{{ __('bloodcare.appointments.eligibility_review') }}</option>
                        <option value="Consultation">{{ __('bloodcare.appointments.consultation') }}</option>
                    </select>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.appointments.centre') }}</span>
                    <select class="form-select" id="bc-appointment-centre-field" name="centre" required>
                        @foreach ($appointmentData['centres'] as $centre)
                            <option value="{{ $centre }}">{{ $centre }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.appointments.status') }}</span>
                    <select class="form-select" id="bc-appointment-status-field" name="status" required>
                        <option value="Pending">{{ __('bloodcare.appointments.pending') }}</option>
                        <option value="Confirmed">{{ __('bloodcare.appointments.confirmed') }}</option>
                        <option value="Checked in">{{ __('bloodcare.appointments.checked_in') }}</option>
                    </select>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.appointments.source') }}</span>
                    <select class="form-select" id="bc-appointment-source" name="source" required>
                        <option value="Staff">{{ __('bloodcare.appointments.staff') }}</option>
                        <option value="Public booking">{{ __('bloodcare.appointments.public_booking') }}</option>
                    </select>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.appointments.staff_member') }}</span>
                    <input class="form-control" id="bc-appointment-staff" name="staff" type="text"
                           value="{{ $appointmentData['staffName'] }}" readonly>
                </label>
            </div>

            <label class="bc-field">
                <span>{{ __('bloodcare.appointments.notes') }}</span>
                <textarea class="form-control" id="bc-appointment-notes" name="notes" rows="3" maxlength="600"
                          placeholder="{{ __('bloodcare.appointments.notes_placeholder') }}"></textarea>
            </label>

            <footer class="bc-modal-footer">
                <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.appointments.cancel') }}</button>
                <button class="btn bc-btn-primary" id="bc-appointment-submit" type="submit">
                    <i class="la la-calendar-plus"></i> <span>{{ __('bloodcare.appointments.save_appointment') }}</span>
                </button>
            </footer>
        </form>
    </section>
</div>

<div class="bc-modal" id="bc-appointment-details" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.appointments.close') }}"></button>
    <section class="bc-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="bc-appointment-details-title">
        <header class="bc-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.appointments.details_eyebrow') }}</p>
                <h2 id="bc-appointment-details-title">{{ __('bloodcare.appointments.details_title') }}</h2>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.appointments.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>
        <div class="bc-unit-details" id="bc-appointment-details-content"></div>
        <footer class="bc-modal-footer" id="bc-appointment-details-actions">
            <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.appointments.close') }}</button>
        </footer>
    </section>
</div>

<div class="bc-modal" id="bc-appointment-data-source-modal" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.appointments.close') }}"></button>
    <section class="bc-modal-dialog bc-modal-dialog-small" role="dialog" aria-modal="true" aria-labelledby="bc-appointment-data-source-title">
        <header class="bc-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.appointments.data_source_eyebrow') }}</p>
                <h2 id="bc-appointment-data-source-title">{{ __('bloodcare.appointments.data_source_title') }}</h2>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.appointments.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>
        <div class="bc-data-source-copy">
            <span class="bc-data-source-icon"><i class="la la-link"></i></span>
            <div>
                <strong>{{ __('bloodcare.appointments.browser_storage_title') }}</strong>
                <p>{{ __('bloodcare.appointments.browser_storage_text') }}</p>
            </div>
        </div>
        <footer class="bc-modal-footer">
            <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.appointments.close') }}</button>
            <button class="btn bc-btn-danger-outline" id="bc-reset-appointments" type="button" hidden>
                <i class="la la-undo"></i> {{ __('bloodcare.appointments.reset_sample') }}
            </button>
        </footer>
    </section>
</div>

<div class="bc-modal" id="bc-centre-manager" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.appointments.close') }}"></button>
    <section class="bc-modal-dialog bc-modal-dialog-wide" role="dialog" aria-modal="true"
             aria-labelledby="bc-centre-manager-title">
        <header class="bc-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.appointments.centres_eyebrow') }}</p>
                <h2 id="bc-centre-manager-title">{{ __('bloodcare.appointments.centres_title') }}</h2>
                <p>{{ __('bloodcare.appointments.centres_help') }}</p>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.appointments.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>

        <div class="bc-centre-manager-layout">
            <div class="bc-centre-list-panel">
                <div class="bc-centre-list-heading">
                    <strong>{{ __('bloodcare.appointments.available_centres') }}</strong>
                    <span id="bc-centre-count"></span>
                </div>
                <div class="bc-centre-list" id="bc-centre-list"></div>
            </div>

            <form class="bc-centre-form-panel" id="bc-centre-form">
                <input id="bc-centre-id" name="id" type="hidden">
                <div class="bc-centre-form-heading">
                    <span class="bc-data-source-icon"><i class="la la-hospital"></i></span>
                    <div>
                        <strong id="bc-centre-form-title">{{ __('bloodcare.appointments.add_centre') }}</strong>
                        <small>{{ __('bloodcare.appointments.centre_form_help') }}</small>
                    </div>
                </div>
                <div class="bc-form-alert-error" id="bc-centre-form-error" tabindex="-1" hidden></div>

                <div class="bc-form-grid bc-form-grid-two">
                    <label class="bc-field">
                        <span>{{ __('bloodcare.appointments.centre_name') }}</span>
                        <input class="form-control" id="bc-centre-name" name="name" type="text"
                               maxlength="120" required>
                    </label>
                    <label class="bc-field">
                        <span>{{ __('bloodcare.appointments.region') }}</span>
                        <input class="form-control" id="bc-centre-region" name="region" type="text"
                               maxlength="120" placeholder="{{ __('bloodcare.appointments.region_placeholder') }}" required>
                    </label>
                    <label class="bc-field">
                        <span>{{ __('bloodcare.appointments.township') }}</span>
                        <input class="form-control" id="bc-centre-township" name="township" type="text"
                               maxlength="120" required>
                    </label>
                    <label class="bc-field">
                        <span>{{ __('bloodcare.appointments.centre_phone') }}</span>
                        <input class="form-control" id="bc-centre-phone" name="phone" type="text" maxlength="40">
                    </label>
                    <label class="bc-field">
                        <span>{{ __('bloodcare.appointments.opening_hours') }}</span>
                        <input class="form-control" id="bc-centre-hours" name="hours" type="text"
                               maxlength="120" placeholder="{{ __('bloodcare.appointments.hours_placeholder') }}">
                    </label>
                    <label class="bc-field bc-centre-active-field">
                        <span>{{ __('bloodcare.appointments.booking_status') }}</span>
                        <span class="bc-centre-switch-row">
                            <input id="bc-centre-active" name="active" type="checkbox" checked>
                            <span>{{ __('bloodcare.appointments.accepting_appointments') }}</span>
                        </span>
                    </label>
                </div>

                <label class="bc-field">
                    <span>{{ __('bloodcare.appointments.centre_address') }}</span>
                    <textarea class="form-control" id="bc-centre-address" name="address" rows="2"
                              maxlength="300" required></textarea>
                </label>

                <footer class="bc-centre-form-actions">
                    <button class="btn bc-btn-outline" id="bc-centre-cancel-edit" type="button" hidden>
                        {{ __('bloodcare.appointments.cancel_edit') }}
                    </button>
                    <button class="btn bc-btn-primary" id="bc-centre-submit" type="submit">
                        <i class="la la-plus-circle"></i>
                        <span>{{ __('bloodcare.appointments.save_centre') }}</span>
                    </button>
                </footer>
            </form>
        </div>

        <footer class="bc-modal-footer">
            <span class="bc-centre-database-note">
                <i class="la la-database"></i>
                {{ __('bloodcare.appointments.centres_database_note') }}
            </span>
            <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.appointments.close') }}</button>
        </footer>
    </section>
</div>

<div class="bc-toast" id="bc-appointment-toast" role="status" aria-live="polite" hidden>
    <i class="la la-check-circle"></i>
    <span></span>
</div>
