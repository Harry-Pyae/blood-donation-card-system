@php
    $inventoryRoutePrefix = backpack_user()?->isLaboratoryUser() ? 'bloodcare.lab.inventory' : 'bloodcare.admin.inventory';
    $inventoryConfig = [
        ...$inventoryData,
        'locale' => app()->getLocale(),
        'csrfToken' => csrf_token(),
        'storeUrl' => route($inventoryRoutePrefix.'.store'),
        'detailsUrlTemplate' => route($inventoryRoutePrefix.'.details', ['unit' => '__REFERENCE__']),
        'updateUrlTemplate' => route($inventoryRoutePrefix.'.update', ['unit' => '__REFERENCE__']),
        'statusUrlTemplate' => route($inventoryRoutePrefix.'.status', ['unit' => '__REFERENCE__']),
        'labels' => [
            'available' => __('bloodcare.dashboard.status.available'),
            'reserved' => __('bloodcare.dashboard.status.reserved'),
            'quarantined' => __('bloodcare.dashboard.status.quarantined'),
            'expiring' => __('bloodcare.dashboard.status.expiring'),
            'expired' => __('bloodcare.dashboard.status.expired'),
            'used' => __('bloodcare.dashboard.status.used'),
            'discarded' => __('bloodcare.dashboard.status.discarded'),
            'healthy' => __('bloodcare.dashboard.status.healthy'),
            'low' => __('bloodcare.dashboard.status.low'),
            'critical' => __('bloodcare.dashboard.status.critical'),
            'units' => __('bloodcare.module.units'),
            'showingRange' => __('bloodcare.inventory.showing_range'),
            'noResults' => __('bloodcare.inventory.no_results'),
            'view' => __('bloodcare.inventory.view_unit'),
            'edit' => __('bloodcare.inventory.edit_unit'),
            'reserve' => __('bloodcare.inventory.reserve_unit'),
            'release' => __('bloodcare.inventory.release_unit'),
            'markUsed' => __('bloodcare.inventory.mark_used'),
            'discard' => __('bloodcare.inventory.discard_unit'),
            'actionsFor' => __('bloodcare.inventory.open_actions'),
            'confirmUsed' => __('bloodcare.inventory.confirm_used'),
            'confirmDiscard' => __('bloodcare.inventory.confirm_discard'),
            'confirmReset' => __('bloodcare.inventory.confirm_reset'),
            'saved' => __('bloodcare.inventory.saved'),
            'reservedMessage' => __('bloodcare.inventory.reserved_message'),
            'releasedMessage' => __('bloodcare.inventory.released_message'),
            'usedMessage' => __('bloodcare.inventory.used_message'),
            'discardedMessage' => __('bloodcare.inventory.discarded_message'),
            'resetMessage' => __('bloodcare.inventory.reset_message'),
            'duplicateUnit' => __('bloodcare.inventory.duplicate_unit'),
            'invalidDates' => __('bloodcare.inventory.invalid_dates'),
            'filterGroup' => __('bloodcare.inventory.filter_group'),
            'traceQrTitle' => __('bloodcare.inventory.trace_qr_title'),
            'traceQrHelp' => __('bloodcare.inventory.trace_qr_help'),
            'openTrace' => __('bloodcare.inventory.open_trace'),
            'qrUnavailable' => __('bloodcare.inventory.qr_unavailable'),
            'detailsOverview' => __('bloodcare.inventory.details_overview'),
            'detailsLaboratory' => __('bloodcare.inventory.details_laboratory'),
            'detailsLaboratoryHelp' => __('bloodcare.inventory.details_laboratory_help'),
            'detailsLineage' => __('bloodcare.inventory.details_lineage'),
            'detailsLineageHelp' => __('bloodcare.inventory.details_lineage_help'),
            'detailsHaemovigilance' => __('bloodcare.inventory.details_haemovigilance'),
            'detailsHaemovigilanceHelp' => __('bloodcare.inventory.details_haemovigilance_help'),
            'noHaemovigilance' => __('bloodcare.inventory.no_haemovigilance'),
            'haemoReference' => __('bloodcare.inventory.haemovigilance_reference'),
            'haemoSeverity' => __('bloodcare.national.haemovigilance.severity'),
            'haemoStatus' => __('bloodcare.national.common.status'),
            'haemoSuspectedType' => __('bloodcare.national.haemovigilance.suspected_type'),
            'haemoFinalType' => __('bloodcare.national.haemovigilance.final_type'),
            'haemoImputability' => __('bloodcare.national.haemovigilance.imputability'),
            'haemoOutcome' => __('bloodcare.national.haemovigilance.outcome'),
            'haemoOccurredAt' => __('bloodcare.national.haemovigilance.occurred_at'),
            'haemoReviewedAt' => __('bloodcare.inventory.haemovigilance_reviewed_at'),
            'haemoClosedAt' => __('bloodcare.national.haemovigilance.closed_at'),
            'detailsTraceHistory' => __('bloodcare.inventory.details_trace_history'),
            'detailsTraceHelp' => __('bloodcare.inventory.details_trace_help'),
            'component' => __('bloodcare.inventory.component'),
            'donationReference' => __('bloodcare.inventory.donation_reference'),
            'donationCentre' => __('bloodcare.inventory.donation_centre'),
            'componentModifiers' => __('bloodcare.inventory.component_modifiers'),
            'noModifiers' => __('bloodcare.traceability.unit.no_modifiers'),
            'labReference' => __('bloodcare.national.laboratory.lab_reference'),
            'releaseDecision' => __('bloodcare.inventory.release_decision'),
            'testedAt' => __('bloodcare.national.laboratory.tested_at'),
            'releasedAt' => __('bloodcare.inventory.released_at'),
            'testedBy' => __('bloodcare.national.laboratory.tested_by'),
            'releasedBy' => __('bloodcare.inventory.released_by'),
            'labNotes' => __('bloodcare.national.laboratory.lab_notes'),
            'mandatoryTti' => __('bloodcare.national.laboratory.mandatory_tti'),
            'immunohematology' => __('bloodcare.national.laboratory.immunohematology'),
            'regionalTti' => __('bloodcare.national.laboratory.regional_tti'),
            'labPending' => __('bloodcare.inventory.lab_pending'),
            'labUnavailable' => __('bloodcare.inventory.lab_unavailable'),
            'inheritedLab' => __('bloodcare.inventory.inherited_lab'),
            'sourceUnits' => __('bloodcare.traceability.source_units'),
            'currentUnit' => __('bloodcare.inventory.current_unit'),
            'derivedUnits' => __('bloodcare.traceability.derived_units'),
            'noSourceUnits' => __('bloodcare.inventory.no_source_units'),
            'noDerivedUnits' => __('bloodcare.inventory.no_derived_units'),
            'loadingDetails' => __('bloodcare.inventory.loading_details'),
            'detailsLoadError' => __('bloodcare.inventory.details_load_error'),
            'notRecorded' => __('bloodcare.traceability.not_recorded'),
        ],
    ];
@endphp

<script id="bc-inventory-config" type="application/json">@json($inventoryConfig)</script>

<div class="bc-modal" id="bc-inventory-editor" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.inventory.close') }}"></button>
    <section class="bc-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="bc-inventory-editor-title">
        <header class="bc-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.inventory.adjustment_eyebrow') }}</p>
                <h2 id="bc-inventory-editor-title">{{ __('bloodcare.inventory.adjustment_title') }}</h2>
                <p>{{ __('bloodcare.inventory.adjustment_help') }}</p>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.inventory.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>

        <form id="bc-inventory-form">
            <div class="bc-adjustment-mode" role="group" aria-label="{{ __('bloodcare.inventory.adjustment_type') }}">
                <button class="active" type="button" data-adjustment-mode="add">
                    <i class="la la-plus-circle"></i> {{ __('bloodcare.inventory.add_unit') }}
                </button>
                <button type="button" data-adjustment-mode="update">
                    <i class="la la-pen"></i> {{ __('bloodcare.inventory.update_unit') }}
                </button>
            </div>

            <label class="bc-field bc-existing-unit-field" for="bc-existing-unit" hidden>
                <span>{{ __('bloodcare.inventory.choose_unit') }}</span>
                <select class="form-select" id="bc-existing-unit"></select>
            </label>

            <div class="bc-form-alert-error" id="bc-inventory-form-error" hidden></div>

            <div class="bc-form-grid-two">
                <label class="bc-field">
                    <span>{{ __('bloodcare.inventory.unit_number') }}</span>
                    <input class="form-control" id="bc-unit-id" name="unitId" type="text" required
                           pattern="BU-[0-9]{6}" placeholder="BU-026185">
                    <small>{{ __('bloodcare.inventory.unit_number_help') }}</small>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.inventory.blood_group') }}</span>
                    <select class="form-select" id="bc-unit-group" name="group" required>
                        @foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $group)
                            <option value="{{ $group }}">{{ $group }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.inventory.collected_date') }}</span>
                    <input class="form-control" id="bc-unit-collected" name="collected" type="date" required>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.inventory.expiry_date') }}</span>
                    <input class="form-control" id="bc-unit-expires" name="expires" type="date" required>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.inventory.location') }}</span>
                    <select class="form-select" id="bc-unit-location" name="location" required>
                        <option value="Cold room A">{{ __('bloodcare.inventory.cold_room_a') }}</option>
                        <option value="Cold room B">{{ __('bloodcare.inventory.cold_room_b') }}</option>
                        <option value="Cold room C">{{ __('bloodcare.inventory.cold_room_c') }}</option>
                    </select>
                </label>

                <label class="bc-field">
                    <span>{{ __('bloodcare.inventory.status') }}</span>
                    <select class="form-select" id="bc-unit-status" name="status" required>
                        <option value="Available">{{ __('bloodcare.dashboard.status.available') }}</option>
                        <option value="Reserved">{{ __('bloodcare.dashboard.status.reserved') }}</option>
                        <option value="Quarantined">{{ __('bloodcare.dashboard.status.quarantined') }}</option>
                        <option value="Used">{{ __('bloodcare.dashboard.status.used') }}</option>
                        <option value="Discarded">{{ __('bloodcare.dashboard.status.discarded') }}</option>
                    </select>
                </label>
            </div>

            <label class="bc-field">
                <span>{{ __('bloodcare.inventory.note') }}</span>
                <textarea class="form-control" id="bc-unit-note" name="note" rows="3" required maxlength="2000"
                          placeholder="{{ __('bloodcare.inventory.note_placeholder') }}"></textarea>
            </label>

            <footer class="bc-modal-footer">
                <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.inventory.cancel') }}</button>
                <button class="btn bc-btn-primary" type="submit">
                    <i class="la la-check"></i> {{ __('bloodcare.inventory.save_adjustment') }}
                </button>
            </footer>
        </form>
    </section>
</div>

<div class="bc-modal" id="bc-inventory-details" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.inventory.close') }}"></button>
    <section class="bc-modal-dialog bc-inventory-trace-dialog" role="dialog" aria-modal="true" aria-labelledby="bc-inventory-details-title">
        <header class="bc-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.inventory.unit_details_eyebrow') }}</p>
                <h2 id="bc-inventory-details-title">{{ __('bloodcare.inventory.unit_details') }}</h2>
                <p>{{ __('bloodcare.inventory.unit_details_help') }}</p>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.inventory.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>
        <div class="bc-unit-details" id="bc-unit-details-content"></div>
        <footer class="bc-modal-footer">
            <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.inventory.close') }}</button>
            <button class="btn bc-btn-primary" id="bc-details-edit" type="button">
                <i class="la la-pen"></i> {{ __('bloodcare.inventory.edit_unit') }}
            </button>
        </footer>
    </section>
</div>

<div class="bc-modal" id="bc-data-source-modal" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.inventory.close') }}"></button>
    <section class="bc-modal-dialog bc-modal-dialog-small" role="dialog" aria-modal="true" aria-labelledby="bc-data-source-title">
        <header class="bc-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.inventory.data_source_eyebrow') }}</p>
                <h2 id="bc-data-source-title">{{ __('bloodcare.inventory.data_source_title') }}</h2>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.inventory.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>
        <div class="bc-data-source-copy">
            <span class="bc-data-source-icon"><i class="la la-database"></i></span>
            <div>
                <strong>{{ __('bloodcare.inventory.browser_storage_title') }}</strong>
                <p>{{ __('bloodcare.inventory.browser_storage_text') }}</p>
            </div>
        </div>
        <footer class="bc-modal-footer">
            <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.inventory.close') }}</button>
            <button class="btn bc-btn-danger-outline" id="bc-reset-inventory" type="button" hidden>
                <i class="la la-undo"></i> {{ __('bloodcare.inventory.reset_sample') }}
            </button>
        </footer>
    </section>
</div>

<div class="bc-toast" id="bc-inventory-toast" role="status" aria-live="polite" hidden>
    <i class="la la-check-circle"></i>
    <span></span>
</div>
