@php
    $historyConfig = [
        ...$historyData,
        'locale' => app()->getLocale(),
        'labels' => [
            'types' => __('bloodcare.history.types'),
            'actions' => __('bloodcare.history.actions'),
            'results' => __('bloodcare.history.results'),
            'showingRange' => __('bloodcare.history.showing_range'),
            'noResults' => __('bloodcare.history.no_results'),
            'viewEvent' => __('bloodcare.history.view_event'),
            'time' => __('bloodcare.history.time'),
            'activity' => __('bloodcare.history.activity'),
            'reference' => __('bloodcare.history.reference'),
            'staffMember' => __('bloodcare.history.staff_member'),
            'details' => __('bloodcare.history.details'),
            'result' => __('bloodcare.history.result'),
            'donor' => __('bloodcare.history.donor'),
            'donorId' => __('bloodcare.history.donor_id'),
            'eventId' => __('bloodcare.history.event_id'),
            'source' => __('bloodcare.history.source'),
            'notApplicable' => __('bloodcare.history.not_applicable'),
            'close' => __('bloodcare.history.close'),
            'exportedMessage' => __('bloodcare.history.exported_message'),
            'csvColumns' => __('bloodcare.history.csv_columns'),
        ],
    ];
@endphp

<script id="bc-history-config" type="application/json">@json($historyConfig)</script>

<div class="bc-modal" id="bc-history-details" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close
            aria-label="{{ __('bloodcare.history.close') }}"></button>
    <section class="bc-modal-dialog bc-history-dialog" role="dialog" aria-modal="true"
             aria-labelledby="bc-history-details-title">
        <header class="bc-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.history.details_eyebrow') }}</p>
                <h2 id="bc-history-details-title">{{ __('bloodcare.history.details_title') }}</h2>
                <p>{{ __('bloodcare.history.details_help') }}</p>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close
                    aria-label="{{ __('bloodcare.history.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>
        <div class="bc-history-details-content" id="bc-history-details-content"></div>
        <footer class="bc-modal-footer">
            <span class="bc-history-read-only">
                <i class="la la-lock"></i> {{ __('bloodcare.history.read_only') }}
            </span>
            <button class="btn bc-btn-outline" type="button" data-modal-close>
                {{ __('bloodcare.history.close') }}
            </button>
        </footer>
    </section>
</div>

<div class="bc-modal" id="bc-history-data-source-modal" hidden>
    <button class="bc-modal-backdrop" type="button" data-modal-close
            aria-label="{{ __('bloodcare.history.close') }}"></button>
    <section class="bc-modal-dialog bc-modal-dialog-small" role="dialog" aria-modal="true"
             aria-labelledby="bc-history-data-source-title">
        <header class="bc-modal-header">
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.history.data_source_eyebrow') }}</p>
                <h2 id="bc-history-data-source-title">{{ __('bloodcare.history.data_source_title') }}</h2>
            </div>
            <button class="bc-modal-close" type="button" data-modal-close
                    aria-label="{{ __('bloodcare.history.close') }}">
                <i class="la la-times"></i>
            </button>
        </header>
        <div class="bc-data-source-copy">
            <span class="bc-data-source-icon"><i class="la la-shield-alt"></i></span>
            <div>
                <strong>{{ __('bloodcare.history.browser_storage_title') }}</strong>
                <p>{{ __('bloodcare.history.browser_storage_text') }}</p>
            </div>
        </div>
        <footer class="bc-modal-footer">
            <span class="bc-history-read-only">
                <i class="la la-lock"></i> {{ __('bloodcare.history.read_only') }}
            </span>
            <button class="btn bc-btn-outline" type="button" data-modal-close>
                {{ __('bloodcare.history.close') }}
            </button>
        </footer>
    </section>
</div>

<div class="bc-toast" id="bc-history-toast" role="status" aria-live="polite" hidden>
    <i class="la la-check-circle"></i>
    <span></span>
</div>
