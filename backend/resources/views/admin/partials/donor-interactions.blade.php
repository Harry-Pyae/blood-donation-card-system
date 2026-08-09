@php
    $donorConfig = [
        ...$donorData,
        'locale' => app()->getLocale(),
        'csrfToken' => csrf_token(),
        'storeUrl' => route('bloodcare.admin.donors.store'),
        'updateUrlTemplate' => route('bloodcare.admin.donors.update', ['donor' => '__REFERENCE__']),
        'showUrlTemplate' => route('bloodcare.admin.donors.show', ['donor' => '__REFERENCE__']),
        'editUrlTemplate' => route('bloodcare.admin.donors.edit', ['donor' => '__REFERENCE__']),
        'nrc' => [
            'states' => $donorData['nrcStates'] ?? [],
            'townships' => $donorData['nrcTownships'] ?? [],
            'types' => $donorData['nrcTypes'] ?? [],
        ],
        'labels' => [
            'active' => __('bloodcare.dashboard.status.active'),
            'pending' => __('bloodcare.dashboard.status.pending'),
            'inactive' => __('bloodcare.donors.inactive'),
            'eligible' => __('bloodcare.donors.eligible'),
            'review' => __('bloodcare.donors.review'),
            'deferred' => __('bloodcare.donors.deferred'),
            'eligibleNow' => __('bloodcare.donors.eligible_now'),
            'pendingReview' => __('bloodcare.donors.pending_review'),
            'deferredUntil' => __('bloodcare.donors.deferred_until'),
            'showingRange' => __('bloodcare.donors.showing_range'),
            'noResults' => __('bloodcare.donors.no_results'),
            'actionsFor' => __('bloodcare.donors.open_actions'),
            'preview' => __('bloodcare.donors.preview'),
            'view' => __('bloodcare.donors.view'),
            'edit' => __('bloodcare.donors.edit'),
            'markEligible' => __('bloodcare.donors.mark_eligible'),
            'sendReview' => __('bloodcare.donors.send_review'),
            'defer' => __('bloodcare.donors.defer'),
            'deactivate' => __('bloodcare.donors.deactivate'),
            'reactivate' => __('bloodcare.donors.reactivate'),
            'registerTitle' => __('bloodcare.donors.register_title'),
            'editTitle' => __('bloodcare.donors.edit_title'),
            'confirmDeactivate' => __('bloodcare.donors.confirm_deactivate'),
            'confirmReset' => __('bloodcare.donors.confirm_reset'),
            'saved' => __('bloodcare.donors.saved'),
            'eligibleMessage' => __('bloodcare.donors.eligible_message'),
            'reviewMessage' => __('bloodcare.donors.review_message'),
            'deactivatedMessage' => __('bloodcare.donors.deactivated_message'),
            'reactivatedMessage' => __('bloodcare.donors.reactivated_message'),
            'resetMessage' => __('bloodcare.donors.reset_message'),
            'duplicatePhone' => __('bloodcare.donors.duplicate_phone'),
            'futureBirthDate' => __('bloodcare.donors.future_birth_date'),
            'deferredDateRequired' => __('bloodcare.donors.deferred_date_required'),
            'notRecorded' => __('bloodcare.donors.not_recorded'),
            'donorId' => __('bloodcare.donors.donor_id'),
            'fullName' => __('bloodcare.donors.full_name'),
            'bloodGroup' => __('bloodcare.donors.blood_group'),
            'phone' => __('bloodcare.donors.phone'),
            'email' => __('bloodcare.donors.email'),
            'dateOfBirth' => __('bloodcare.donors.date_of_birth'),
            'gender' => __('bloodcare.donors.gender'),
            'male' => __('bloodcare.donors.male'),
            'female' => __('bloodcare.donors.female'),
            'other' => __('bloodcare.donors.other'),
            'identity' => __('bloodcare.donors.identity'),
            'identityTitle' => __('bloodcare.public.registration.identity_title'),
            'identityHelp' => __('bloodcare.public.registration.identity_help'),
            'documentType' => __('bloodcare.public.registration.document_type'),
            'documentNrc' => __('bloodcare.public.registration.document_nrc'),
            'documentPassport' => __('bloodcare.public.registration.document_passport'),
            'nrcTitle' => __('bloodcare.public.registration.nrc_title'),
            'nrcHelp' => __('bloodcare.public.registration.nrc_help'),
            'nrcState' => __('bloodcare.public.registration.nrc_state'),
            'selectState' => __('bloodcare.public.registration.select_state'),
            'nrcTownship' => __('bloodcare.public.registration.nrc_township'),
            'selectStateFirst' => __('bloodcare.public.registration.select_state_first'),
            'selectTownship' => __('bloodcare.public.registration.select_township'),
            'nrcType' => __('bloodcare.public.registration.nrc_type'),
            'selectNrcType' => __('bloodcare.public.registration.select_nrc_type'),
            'nrcSerial' => __('bloodcare.public.registration.nrc_serial'),
            'nrcSerialHelp' => __('bloodcare.public.registration.nrc_serial_help'),
            'nrcSerialPlaceholder' => __('bloodcare.public.registration.nrc_serial_placeholder'),
            'nrcPreview' => __('bloodcare.public.registration.nrc_preview'),
            'nrcPreviewEmpty' => __('bloodcare.public.registration.nrc_preview_empty'),
            'passportNumber' => __('bloodcare.public.registration.passport_number'),
            'passportHelp' => __('bloodcare.public.registration.passport_help'),
            'passportPlaceholder' => __('bloodcare.public.registration.passport_placeholder'),
            'address' => __('bloodcare.donors.address'),
            'lastDonation' => __('bloodcare.donors.last_donation'),
            'totalDonations' => __('bloodcare.donor_details.total_donations'),
            'nextEligible' => __('bloodcare.donors.next_eligible'),
            'eligibility' => __('bloodcare.donors.eligibility'),
            'status' => __('bloodcare.donors.status'),
            'notes' => __('bloodcare.donors.notes'),
            'linkedUser' => __('bloodcare.donor_details.linked_user'),
            'donationPreference' => __('bloodcare.donor_details.donation_preference'),
            'currentDeferral' => __('bloodcare.donor_details.current_deferral'),
            'latestScreening' => __('bloodcare.donor_details.latest_screening'),
            'deferralNone' => __('bloodcare.donor_details.deferral_none'),
        ],
    ];
@endphp

<script id="bc-donor-config" type="application/json">@json($donorConfig)</script>

<div class="bc-modal" id="bc-donor-registration-choice" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.donors.close') }}"></button>
    <section class="bc-modal-dialog bc-registration-choice-dialog" role="dialog" aria-modal="true"
             aria-labelledby="bc-donor-registration-choice-title" aria-describedby="bc-donor-registration-choice-help">
        <header class="bc-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.donors.workflow_eyebrow') }}</p>
                <h2 id="bc-donor-registration-choice-title">{{ __('bloodcare.donor_details.registration_choice_title') }}</h2>
                <p id="bc-donor-registration-choice-help">{{ __('bloodcare.donor_details.registration_choice_help') }}</p>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.donors.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>

        <div class="bc-registration-choice-grid">
            <a class="bc-registration-choice-card" data-bc-registration-choice="linked"
               href="{{ route('bloodcare.admin.donors.create', ['mode' => 'linked']) }}">
                <span class="bc-registration-choice-icon"><i class="la la-user-check"></i></span>
                <span>
                    <strong>{{ __('bloodcare.donor_details.register_existing_donor') }}</strong>
                    <small>{{ __('bloodcare.donor_details.register_existing_donor_help') }}</small>
                </span>
                <i class="la la-arrow-right" aria-hidden="true"></i>
            </a>
            <a class="bc-registration-choice-card" data-bc-registration-choice="new"
               href="{{ route('bloodcare.admin.donors.create', ['mode' => 'new']) }}">
                <span class="bc-registration-choice-icon"><i class="la la-user-plus"></i></span>
                <span>
                    <strong>{{ __('bloodcare.donor_details.register_new_donor') }}</strong>
                    <small>{{ __('bloodcare.donor_details.register_new_donor_help') }}</small>
                </span>
                <i class="la la-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </section>
</div>

<div class="bc-modal" id="bc-donor-editor" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.donors.close') }}"></button>
    <section class="bc-modal-dialog bc-modal-dialog-wide" role="dialog" aria-modal="true" aria-labelledby="bc-donor-editor-title">
        <header class="bc-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.donors.workflow_eyebrow') }}</p>
                <h2 id="bc-donor-editor-title">{{ __('bloodcare.donors.register_title') }}</h2>
                <p>{{ __('bloodcare.donors.form_help') }}</p>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.donors.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>

        <form id="bc-donor-form">
            <div class="bc-form-alert-error" id="bc-donor-form-error" hidden></div>

            <div class="bc-form-grid bc-form-grid-two">
                <label class="bc-field">
                    <span>{{ __('bloodcare.donors.donor_id') }}</span>
                    <input class="form-control" id="bc-donor-id" name="donorId" type="text" readonly>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donors.full_name') }}</span>
                    <input class="form-control" id="bc-donor-name" name="name" type="text" required maxlength="100">
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donors.blood_group') }}</span>
                    <select class="form-select" id="bc-donor-form-group" name="group" required>
                        @foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $group)
                            <option value="{{ $group }}">{{ $group }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donors.phone') }}</span>
                    <input class="form-control" id="bc-donor-phone" name="phone" type="tel" required maxlength="30">
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donors.email') }}</span>
                    <input class="form-control" id="bc-donor-email" name="email" type="email" maxlength="120">
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donors.date_of_birth') }}</span>
                    <input class="form-control" id="bc-donor-birth" name="dateOfBirth" type="date" required
                           data-bc-date-purpose="birth" max="{{ $donorData['today'] }}">
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donors.gender') }}</span>
                    <select class="form-select" id="bc-donor-gender" name="gender" required>
                        <option value="Male">{{ __('bloodcare.donors.male') }}</option>
                        <option value="Female">{{ __('bloodcare.donors.female') }}</option>
                        <option value="Other">{{ __('bloodcare.donors.other') }}</option>
                    </select>
                </label>
            </div>

            <section class="bc-admin-identity-section" aria-labelledby="bc-donor-identity-heading">
                <header class="bc-admin-form-section-heading">
                    <span class="bc-admin-form-section-icon"><i class="la la-id-card"></i></span>
                    <div>
                        <h3 id="bc-donor-identity-heading">{{ __('bloodcare.public.registration.identity_title') }}</h3>
                        <p>{{ __('bloodcare.public.registration.identity_help') }}</p>
                    </div>
                </header>

                <label class="bc-field bc-admin-document-type-field">
                    <span>{{ __('bloodcare.public.registration.document_type') }}</span>
                    <select class="form-select" id="bc-donor-document-type" data-bc-styled-select name="identityDocumentType" required>
                        <option value="nrc">{{ __('bloodcare.public.registration.document_nrc') }}</option>
                        <option value="passport">{{ __('bloodcare.public.registration.document_passport') }}</option>
                    </select>
                </label>

                <fieldset class="bc-admin-nrc-card" id="bc-donor-nrc-fields" data-admin-nrc-fields>
                    <legend>{{ __('bloodcare.public.registration.nrc_title') }}</legend>
                    <p>{{ __('bloodcare.public.registration.nrc_help') }}</p>

                    <div class="bc-admin-nrc-grid">
                        <label class="bc-field">
                            <span>{{ __('bloodcare.public.registration.nrc_state') }}</span>
                            <select class="form-select" id="bc-donor-nrc-state" data-bc-styled-select name="nrcState" required>
                                <option value="">{{ __('bloodcare.public.registration.select_state') }}</option>
                                @foreach (($donorData['nrcStates'] ?? []) as $code => $state)
                                    <option value="{{ $code }}">
                                        {{ $code }} — {{ app()->isLocale('my') ? $state['my'] : $state['en'] }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="bc-field">
                            <span>{{ __('bloodcare.public.registration.nrc_township') }}</span>
                            <select class="form-select" id="bc-donor-nrc-township" data-bc-styled-select name="nrcTownship" required disabled>
                                <option value="">{{ __('bloodcare.public.registration.select_state_first') }}</option>
                            </select>
                        </label>

                        <label class="bc-field">
                            <span>{{ __('bloodcare.public.registration.nrc_type') }}</span>
                            <select class="form-select" id="bc-donor-nrc-type" data-bc-styled-select name="nrcType" required>
                                <option value="">{{ __('bloodcare.public.registration.select_nrc_type') }}</option>
                                @foreach (($donorData['nrcTypes'] ?? []) as $code => $type)
                                    <option value="{{ $code }}">
                                        ({{ $code }}) {{ app()->isLocale('my') ? $type['my'] : $type['en'] }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="bc-field">
                            <span>{{ __('bloodcare.public.registration.nrc_serial') }}</span>
                            <input class="form-control" id="bc-donor-nrc-serial" name="nrcSerial" type="text"
                                   inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required
                                   placeholder="{{ __('bloodcare.public.registration.nrc_serial_placeholder') }}">
                            <small>{{ __('bloodcare.public.registration.nrc_serial_help') }}</small>
                        </label>
                    </div>

                    <div class="bc-admin-nrc-preview" aria-live="polite">
                        <span>{{ __('bloodcare.public.registration.nrc_preview') }}</span>
                        <strong id="bc-donor-nrc-preview">{{ __('bloodcare.public.registration.nrc_preview_empty') }}</strong>
                    </div>
                </fieldset>

                <label class="bc-field bc-admin-passport-field" id="bc-donor-passport-fields" hidden>
                    <span>{{ __('bloodcare.public.registration.passport_number') }}</span>
                    <input class="form-control" id="bc-donor-passport-number" name="passportNumber" type="text"
                           minlength="5" maxlength="20" pattern="[A-Za-z0-9]+" disabled
                           placeholder="{{ __('bloodcare.public.registration.passport_placeholder') }}">
                    <small>{{ __('bloodcare.public.registration.passport_help') }}</small>
                </label>
            </section>

            <div class="bc-form-grid bc-form-grid-two bc-donor-status-grid">
                <label class="bc-field">
                    <span>{{ __('bloodcare.donors.last_donation') }}</span>
                    <input class="form-control" id="bc-donor-last-donation" name="lastDonation" type="date"
                           max="{{ $donorData['today'] }}">
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donors.next_eligible') }}</span>
                    <input class="form-control" id="bc-donor-next-eligible" name="nextEligible" type="date">
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donors.eligibility') }}</span>
                    <select class="form-select" id="bc-donor-form-eligibility" name="eligibility" required>
                        <option value="Eligible">{{ __('bloodcare.donors.eligible') }}</option>
                        <option value="Review">{{ __('bloodcare.donors.review') }}</option>
                        <option value="Deferred">{{ __('bloodcare.donors.deferred') }}</option>
                    </select>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.donors.status') }}</span>
                    <select class="form-select" id="bc-donor-form-status" name="status" required>
                        <option value="Active">{{ __('bloodcare.dashboard.status.active') }}</option>
                        <option value="Pending">{{ __('bloodcare.dashboard.status.pending') }}</option>
                        <option value="Inactive">{{ __('bloodcare.donors.inactive') }}</option>
                    </select>
                </label>
            </div>

            <label class="bc-field">
                <span>{{ __('bloodcare.donors.address') }}</span>
                <textarea class="form-control" id="bc-donor-address" name="address" rows="2" maxlength="300"></textarea>
            </label>

            <label class="bc-field">
                <span>{{ __('bloodcare.donors.notes') }}</span>
                <textarea class="form-control" id="bc-donor-notes" name="notes" rows="3" maxlength="500"
                          placeholder="{{ __('bloodcare.donors.notes_placeholder') }}"></textarea>
            </label>

            <footer class="bc-modal-footer">
                <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.donors.cancel') }}</button>
                <button class="btn bc-btn-primary" type="submit">
                    <i class="la la-check"></i> {{ __('bloodcare.donors.save_donor') }}
                </button>
            </footer>
        </form>
    </section>
</div>

<div class="bc-modal" id="bc-donor-details" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.donors.close') }}"></button>
    <section class="bc-modal-dialog bc-modal-dialog-small" role="dialog" aria-modal="true" aria-labelledby="bc-donor-details-title">
        <header class="bc-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.donors.details_eyebrow') }}</p>
                <h2 id="bc-donor-details-title">{{ __('bloodcare.donors.preview_title') }}</h2>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.donors.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>
        <div class="bc-unit-details" id="bc-donor-details-content"></div>
        <footer class="bc-modal-footer">
            <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.donors.close') }}</button>
            <a class="btn bc-btn-outline" id="bc-donor-details-edit" href="#"><i class="la la-pen"></i> {{ __('bloodcare.donors.edit') }}</a>
            <a class="btn bc-btn-primary" id="bc-donor-details-full" href="#"><i class="la la-folder-open"></i> {{ __('bloodcare.donors.view') }}</a>
        </footer>
    </section>
</div>

<div class="bc-modal" id="bc-donor-data-source-modal" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.donors.close') }}"></button>
    <section class="bc-modal-dialog bc-modal-dialog-small" role="dialog" aria-modal="true" aria-labelledby="bc-donor-data-source-title">
        <header class="bc-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.donors.data_source_eyebrow') }}</p>
                <h2 id="bc-donor-data-source-title">{{ __('bloodcare.donors.data_source_title') }}</h2>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.donors.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>
        <div class="bc-data-source-copy">
            <span class="bc-data-source-icon"><i class="la la-users"></i></span>
            <div>
                <strong>{{ __('bloodcare.donors.browser_storage_title') }}</strong>
                <p>{{ __('bloodcare.donors.browser_storage_text') }}</p>
            </div>
        </div>
        <footer class="bc-modal-footer">
            <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.donors.close') }}</button>
            <button class="btn bc-btn-danger-outline" id="bc-reset-donors" type="button" hidden>
                <i class="la la-undo"></i> {{ __('bloodcare.donors.reset_sample') }}
            </button>
        </footer>
    </section>
</div>

<div class="bc-toast" id="bc-donor-toast" role="status" aria-live="polite" hidden>
    <i class="la la-check-circle"></i>
    <span></span>
</div>
