@php
    $cardConfig = [
        ...$cardData,
        'locale' => app()->getLocale(),
        'csrfToken' => csrf_token(),
        'storeUrl' => route('bloodcare.admin.cards.store'),
        'donorSearchUrl' => route('bloodcare.admin.cards.donors.search'),
        'updateUrlTemplate' => route('bloodcare.admin.cards.update', ['card' => '__REFERENCE__']),
        'labels' => [
            'active' => __('bloodcare.dashboard.status.active'),
            'pending' => __('bloodcare.dashboard.status.pending'),
            'expiring' => __('bloodcare.dashboard.status.expiring'),
            'expired' => __('bloodcare.dashboard.status.expired'),
            'suspended' => __('bloodcare.cards.suspended'),
            'notIssued' => __('bloodcare.cards.not_issued'),
            'showingRange' => __('bloodcare.cards.showing_range'),
            'noResults' => __('bloodcare.cards.no_results'),
            'actionsFor' => __('bloodcare.cards.open_actions'),
            'view' => __('bloodcare.cards.view'),
            'issueCard' => __('bloodcare.cards.issue_card'),
            'print' => __('bloodcare.cards.print'),
            'renew' => __('bloodcare.cards.renew'),
            'replaceLost' => __('bloodcare.cards.replace_lost'),
            'suspend' => __('bloodcare.cards.suspend'),
            'reactivate' => __('bloodcare.cards.reactivate'),
            'confirmRenew' => __('bloodcare.cards.confirm_renew'),
            'confirmSuspend' => __('bloodcare.cards.confirm_suspend'),
            'confirmReplace' => __('bloodcare.cards.confirm_replace'),
            'confirmReset' => __('bloodcare.cards.confirm_reset'),
            'resetSample' => __('bloodcare.cards.reset_sample'),
            'confirmEyebrow' => __('bloodcare.cards.confirm_eyebrow'),
            'renewTitle' => __('bloodcare.cards.renew_title'),
            'suspendTitle' => __('bloodcare.cards.suspend_title'),
            'replaceTitle' => __('bloodcare.cards.replace_title'),
            'resetTitle' => __('bloodcare.cards.reset_title'),
            'confirmAction' => __('bloodcare.cards.confirm_action'),
            'issuedMessage' => __('bloodcare.cards.issued_message'),
            'renewedMessage' => __('bloodcare.cards.renewed_message'),
            'suspendedMessage' => __('bloodcare.cards.suspended_message'),
            'reactivatedMessage' => __('bloodcare.cards.reactivated_message'),
            'replacedMessage' => __('bloodcare.cards.replaced_message'),
            'printedMessage' => __('bloodcare.cards.printed_message'),
            'resetMessage' => __('bloodcare.cards.reset_message'),
            'invalidDates' => __('bloodcare.cards.invalid_dates'),
            'duplicateCard' => __('bloodcare.cards.duplicate_card'),
            'donorNotVerified' => __('bloodcare.cards.donor_not_verified'),
            'noVerifiedDonors' => __('bloodcare.cards.no_verified_donors'),
            'copiedMessage' => __('bloodcare.cards.copied_message'),
            'chooseDonorPlaceholder' => __('bloodcare.cards.choose_donor_placeholder'),
            'noDonorMatches' => __('bloodcare.cards.no_donor_matches'),
            'searchingDonors' => __('bloodcare.cards.searching_donors'),
            'donorSearchUnavailable' => __('bloodcare.cards.donor_search_unavailable'),
            'cardNumber' => __('bloodcare.cards.card_number'),
            'bloodGroup' => __('bloodcare.cards.blood_group'),
            'phone' => __('bloodcare.cards.phone'),
            'issueDate' => __('bloodcare.cards.issue_date'),
            'expiryDate' => __('bloodcare.cards.expiry_date'),
            'notes' => __('bloodcare.cards.notes'),
            'secureReference' => __('bloodcare.cards.secure_reference'),
            'cardVersion' => __('bloodcare.cards.card_version'),
            'printCount' => __('bloodcare.cards.print_count'),
            'versionValue' => __('bloodcare.cards.version_value'),
            'printsValue' => __('bloodcare.cards.prints_value'),
            'issuedOn' => __('bloodcare.cards.issued_on'),
            'validUntil' => __('bloodcare.cards.valid_until'),
            'donor' => __('bloodcare.cards.donor'),
            'status' => __('bloodcare.cards.status'),
            'notRecorded' => __('bloodcare.cards.not_recorded'),
            'publicSafe' => __('bloodcare.cards.public_safe'),
            'previewTab' => __('bloodcare.cards.preview_tab'),
            'detailsTab' => __('bloodcare.cards.details_tab'),
            'recordSummary' => __('bloodcare.cards.record_summary'),
            'frontSide' => __('bloodcare.cards.front_side'),
            'backSide' => __('bloodcare.cards.back_side'),
            'scanToVerify' => __('bloodcare.cards.scan_to_verify'),
            'scanHelp' => __('bloodcare.cards.scan_help'),
            'qrUnavailable' => __('bloodcare.cards.qr_unavailable'),
            'privacyProtected' => __('bloodcare.cards.privacy_protected'),
            'liveVerification' => __('bloodcare.cards.live_verification'),
            'backDisclaimer' => __('bloodcare.cards.back_disclaimer'),
        ],
    ];
@endphp

<script id="bc-card-config" type="application/json">@json($cardConfig)</script>

<div class="bc-modal" id="bc-card-editor" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.cards.close') }}"></button>
    <section class="bc-modal-dialog bc-modal-dialog-wide" role="dialog" aria-modal="true" aria-labelledby="bc-card-editor-title">
        <header class="bc-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.cards.workflow_eyebrow') }}</p>
                <h2 id="bc-card-editor-title">{{ __('bloodcare.cards.issue_title') }}</h2>
                <p>{{ __('bloodcare.cards.issue_help') }}</p>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.cards.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>

        <form id="bc-card-form">
            <div class="bc-form-alert-error" id="bc-card-form-error" tabindex="-1" hidden></div>

            <div class="bc-card-issue-sections">
                <section class="bc-card-issue-section" aria-labelledby="bc-card-donor-section-title">
                    <header class="bc-card-issue-section-header">
                        <span class="bc-card-issue-section-icon" aria-hidden="true"><i class="la la-user-check"></i></span>
                        <div>
                            <h3 id="bc-card-donor-section-title">{{ __('bloodcare.cards.donor_section_title') }}</h3>
                            <p>{{ __('bloodcare.cards.donor_section_help') }}</p>
                        </div>
                    </header>

                    <select class="visually-hidden" id="bc-card-donor" name="donorId" required
                            data-bc-field-label="{{ __('bloodcare.cards.donor_search') }}"
                            tabindex="-1" aria-hidden="true">
                        <option value="">{{ __('bloodcare.cards.choose_donor_placeholder') }}</option>
                    </select>

                    <label class="bc-card-donor-search" for="bc-card-donor-search">
                        <span>{{ __('bloodcare.cards.donor_search') }}</span>
                        <span class="bc-card-donor-search-control">
                            <i class="la la-search" aria-hidden="true"></i>
                            <input class="form-control" id="bc-card-donor-search" type="search"
                                   autocomplete="off" placeholder="{{ __('bloodcare.cards.donor_search_placeholder') }}">
                        </span>
                    </label>

                    <div class="bc-card-donor-results" id="bc-card-donor-results" role="listbox"
                         aria-label="{{ __('bloodcare.cards.choose_donor') }}"></div>
                </section>

                <section class="bc-card-issue-section" aria-labelledby="bc-card-details-section-title">
                    <header class="bc-card-issue-section-header">
                        <span class="bc-card-issue-section-icon" aria-hidden="true"><i class="la la-id-card"></i></span>
                        <div>
                            <h3 id="bc-card-details-section-title">{{ __('bloodcare.cards.card_section_title') }}</h3>
                            <p>{{ __('bloodcare.cards.card_section_help') }}</p>
                        </div>
                    </header>

                    <div class="bc-form-grid bc-form-grid-two">
                        <label class="bc-field">
                            <span>{{ __('bloodcare.cards.card_number') }}</span>
                            <input class="form-control" id="bc-card-number" name="cardNumber" type="text" readonly>
                        </label>

                        <label class="bc-field">
                            <span>{{ __('bloodcare.cards.blood_group') }}</span>
                            <input class="form-control" id="bc-card-form-group" name="group" type="text" readonly>
                        </label>

                        <label class="bc-field">
                            <span>{{ __('bloodcare.cards.phone') }}</span>
                            <input class="form-control" id="bc-card-form-phone" name="phone" type="text" readonly>
                        </label>

                        <label class="bc-field">
                            <span>{{ __('bloodcare.cards.issue_date') }}</span>
                            <input class="form-control" id="bc-card-issue-date" name="issueDate" type="date"
                                   max="{{ $cardData['today'] }}" required>
                        </label>

                        <label class="bc-field">
                            <span>{{ __('bloodcare.cards.expiry_date') }}</span>
                            <input class="form-control" id="bc-card-expiry-date" name="expiryDate" type="date" required>
                        </label>
                    </div>

                    <label class="bc-field">
                        <span>{{ __('bloodcare.cards.notes') }}</span>
                        <textarea class="form-control" id="bc-card-notes" name="notes" rows="3" maxlength="500"
                                  placeholder="{{ __('bloodcare.cards.notes_placeholder') }}"></textarea>
                    </label>
                </section>
            </div>

            <footer class="bc-modal-footer">
                <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.cards.cancel') }}</button>
                <button class="btn bc-btn-primary" id="bc-card-submit" type="submit">
                    <i class="la la-id-card"></i> {{ __('bloodcare.cards.issue_card') }}
                </button>
            </footer>
        </form>
    </section>
</div>

<div class="bc-modal" id="bc-card-details" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.cards.close') }}"></button>
    <section class="bc-modal-dialog bc-card-preview-dialog" role="dialog" aria-modal="true" aria-labelledby="bc-card-details-title">
        <header class="bc-modal-header bc-card-preview-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.cards.details_eyebrow') }}</p>
                <h2 id="bc-card-details-title">{{ __('bloodcare.cards.details_title') }}</h2>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.cards.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>
        <nav class="bc-card-view-switcher" role="tablist" aria-label="{{ __('bloodcare.cards.view_switcher') }}">
            <button class="bc-card-view-tab active" id="bc-card-preview-tab" type="button" role="tab"
                    data-card-detail-view="preview" aria-selected="true" aria-controls="bc-card-preview-panel">
                <i class="la la-id-card"></i>
                <span>{{ __('bloodcare.cards.preview_tab') }}</span>
            </button>
            <button class="bc-card-view-tab" id="bc-card-information-tab" type="button" role="tab"
                    data-card-detail-view="details" aria-selected="false" tabindex="-1" aria-controls="bc-card-information-panel">
                <i class="la la-list-alt"></i>
                <span>{{ __('bloodcare.cards.details_tab') }}</span>
            </button>
        </nav>
        <div class="bc-card-details-content" id="bc-card-details-content"></div>
        <footer class="bc-modal-footer bc-card-preview-actions">
            <button class="btn bc-btn-outline" id="bc-card-copy" type="button">
                <i class="la la-copy"></i> {{ __('bloodcare.cards.copy_reference') }}
            </button>
            <button class="btn bc-btn-primary" id="bc-card-print" type="button">
                <i class="la la-print"></i> {{ __('bloodcare.cards.print') }}
            </button>
        </footer>
    </section>
</div>

<div class="bc-modal" id="bc-card-confirm" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close
            aria-label="{{ __('bloodcare.cards.cancel') }}"></button>
    <section class="bc-modal-dialog bc-modal-dialog-small bc-confirm-dialog" role="alertdialog" aria-modal="true"
             aria-labelledby="bc-card-confirm-title" aria-describedby="bc-card-confirm-message">
        <header class="bc-confirm-header">
            <span class="bc-confirm-icon" id="bc-card-confirm-icon" aria-hidden="true">
                <i class="la la-exclamation-triangle"></i>
            </span>
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.cards.confirm_eyebrow') }}</p>
                <h2 id="bc-card-confirm-title">{{ __('bloodcare.cards.confirm_action') }}</h2>
            </div>
        </header>
        <p class="bc-confirm-message" id="bc-card-confirm-message"></p>
        <footer class="bc-modal-footer">
            <button class="btn bc-btn-outline" id="bc-card-confirm-cancel" type="button" data-modal-close>
                {{ __('bloodcare.cards.cancel') }}
            </button>
            <button class="btn bc-btn-primary" id="bc-card-confirm-submit" type="button">
                {{ __('bloodcare.cards.confirm_action') }}
            </button>
        </footer>
    </section>
</div>

<div class="bc-modal" id="bc-card-data-source-modal" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.cards.close') }}"></button>
    <section class="bc-modal-dialog bc-modal-dialog-small" role="dialog" aria-modal="true" aria-labelledby="bc-card-data-source-title">
        <header class="bc-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.cards.data_source_eyebrow') }}</p>
                <h2 id="bc-card-data-source-title">{{ __('bloodcare.cards.data_source_title') }}</h2>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.cards.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>
        <div class="bc-data-source-copy">
            <span class="bc-data-source-icon"><i class="la la-id-card"></i></span>
            <div>
                <strong>{{ __('bloodcare.cards.browser_storage_title') }}</strong>
                <p>{{ __('bloodcare.cards.browser_storage_text') }}</p>
            </div>
        </div>
        <footer class="bc-modal-footer">
            <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.cards.close') }}</button>
            <button class="btn bc-btn-danger-outline" id="bc-reset-cards" type="button" hidden>
                <i class="la la-undo"></i> {{ __('bloodcare.cards.reset_sample') }}
            </button>
        </footer>
    </section>
</div>

<div class="bc-toast" id="bc-card-toast" role="status" aria-live="polite" hidden>
    <i class="la la-check-circle"></i>
    <span></span>
</div>
