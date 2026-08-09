@extends(backpack_view('blank'))

@section('title', $title)

@push('before_styles')
    @include('admin.partials.favicon')
@endpush

@push('after_styles')
    <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}?v={{ filemtime(public_path('css/bloodcare-admin.css')) }}">
@endpush

@push('after_scripts')
    <script src="{{ asset('js/bloodcare-reports.js') }}?v={{ filemtime(public_path('js/bloodcare-reports.js')) }}" defer></script>
@endpush

@php
    $reportLabels = [
        'periods' => [
            'month' => __('bloodcare.reports.this_month'),
            '90' => __('bloodcare.reports.last_90_days'),
            'year' => __('bloodcare.reports.this_year'),
            'all' => __('bloodcare.reports.all_time'),
        ],
        'allGroups' => __('bloodcare.reports.all_blood_groups'),
        'eligible' => __('bloodcare.reports.eligible'),
        'deferred' => __('bloodcare.reports.deferred'),
        'review' => __('bloodcare.reports.review'),
        'completed' => __('bloodcare.reports.completed'),
        'confirmed' => __('bloodcare.reports.confirmed'),
        'pending' => __('bloodcare.reports.pending'),
        'cancelled' => __('bloodcare.reports.cancelled'),
        'noShow' => __('bloodcare.reports.no_show'),
        'units' => __('bloodcare.reports.units'),
        'target' => __('bloodcare.reports.target'),
        'donors' => __('bloodcare.reports.donors'),
        'records' => __('bloodcare.reports.records'),
        'criticalStock' => __('bloodcare.reports.critical_stock'),
        'healthyStock' => __('bloodcare.reports.healthy_stock'),
        'pendingReviews' => __('bloodcare.reports.pending_reviews'),
        'screeningQueue' => __('bloodcare.reports.screening_queue'),
        'appointmentQueue' => __('bloodcare.reports.appointment_queue'),
        'noAttention' => __('bloodcare.reports.no_attention'),
        'noCentreData' => __('bloodcare.reports.no_centre_data'),
        'noCentreSearchResults' => __('bloodcare.reports.no_centre_search_results'),
        'showingCentres' => __('bloodcare.reports.showing_centres'),
        'refreshed' => __('bloodcare.reports.refreshed'),
        'exported' => __('bloodcare.reports.exported'),
        'reportGenerated' => __('bloodcare.reports.report_generated'),
        'reportScope' => __('bloodcare.reports.report_scope'),
        'summary' => __('bloodcare.reports.summary'),
        'monthlyActivity' => __('bloodcare.reports.monthly_activity'),
        'stockSummary' => __('bloodcare.reports.stock_summary'),
        'readinessSummary' => __('bloodcare.reports.readiness_summary'),
        'appointmentSummary' => __('bloodcare.reports.appointment_summary'),
        'centreSummary' => __('bloodcare.reports.centre_summary'),
        'metric' => __('bloodcare.reports.metric'),
        'value' => __('bloodcare.reports.value'),
        'month' => __('bloodcare.reports.month'),
        'bloodGroup' => __('bloodcare.reports.blood_group'),
        'status' => __('bloodcare.reports.status'),
        'percentage' => __('bloodcare.reports.percentage'),
        'centre' => __('bloodcare.reports.centre'),
        'bookings' => __('bloodcare.reports.bookings'),
        'attended' => __('bloodcare.reports.attended'),
        'attendanceRate' => __('bloodcare.reports.attendance_rate'),
        'metricDonors' => __('bloodcare.reports.metric_donors'),
        'metricDonations' => __('bloodcare.reports.metric_donations'),
        'metricInventory' => __('bloodcare.reports.metric_inventory'),
        'metricCompletion' => __('bloodcare.reports.metric_completion'),
    ];
    $reportConfig = [...$reportData, 'labels' => $reportLabels];
@endphp

@section('content')
    <div class="bc-admin-page bc-report-page" data-bc-module="reports">
        <header class="bc-page-heading">
            <div class="bc-module-title">
                <span class="bc-module-icon"><i class="la la-chart-bar"></i></span>
                <div>
                    <p class="bc-eyebrow">{{ $eyebrow }}</p>
                    <h1>{{ $title }}</h1>
                    <p>{{ $description }}</p>
                </div>
            </div>
            <div class="bc-heading-tools">
                @include('admin.partials.utility-controls')
                <div class="bc-heading-actions">
                    <button class="bc-prototype-badge" id="bc-report-data-source-button" type="button">
                        <i class="la la-database"></i> {{ __('bloodcare.reports.updated_from_browser') }}
                    </button>
                    <button class="btn bc-btn-primary" id="bc-report-export-button" type="button">
                        <i class="la la-file-export"></i> {{ __('bloodcare.reports.export_report') }}
                    </button>
                </div>
            </div>
        </header>

        <section class="bc-panel bc-report-filter-panel" aria-label="{{ __('bloodcare.reports.filters') }}">
            <div class="bc-report-filter-copy">
                <span><i class="la la-sliders-h"></i></span>
                <div>
                    <strong>{{ __('bloodcare.reports.filters') }}</strong>
                    <small>{{ __('bloodcare.reports.updated_from_browser') }}</small>
                </div>
            </div>

            @foreach ([
                [
                    'id' => 'bc-report-period',
                    'icon' => 'la-calendar-alt',
                    'label' => __('bloodcare.reports.this_month'),
                    'options' => [
                        ['value' => 'month', 'label' => __('bloodcare.reports.this_month')],
                        ['value' => '90', 'label' => __('bloodcare.reports.last_90_days')],
                        ['value' => 'year', 'label' => __('bloodcare.reports.this_year')],
                        ['value' => 'all', 'label' => __('bloodcare.reports.all_time')],
                    ],
                ],
                [
                    'id' => 'bc-report-group',
                    'icon' => 'la-tint',
                    'label' => __('bloodcare.reports.all_blood_groups'),
                    'options' => collect(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])
                        ->map(fn ($group) => ['value' => $group, 'label' => $group])->all(),
                ],
            ] as $filter)
                <div class="bc-filter-dropdown bc-report-filter" data-bc-report-select>
                    <i class="la {{ $filter['icon'] }} bc-filter-dropdown-icon" aria-hidden="true"></i>
                    <select class="visually-hidden" id="{{ $filter['id'] }}" tabindex="-1" aria-hidden="true">
                        @if ($filter['id'] === 'bc-report-group')
                            <option value="all">{{ $filter['label'] }}</option>
                        @endif
                        @foreach ($filter['options'] as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox"
                            aria-expanded="false" aria-controls="{{ $filter['id'] }}-menu">
                        <span data-bc-select-label>{{ $filter['label'] }}</span>
                        <i class="la la-angle-down" aria-hidden="true"></i>
                    </button>
                    <div class="bc-filter-dropdown-menu" id="{{ $filter['id'] }}-menu" role="listbox"
                         aria-label="{{ $filter['label'] }}" hidden>
                        @if ($filter['id'] === 'bc-report-group')
                            <button type="button" role="option" data-value="all" aria-selected="true">
                                <span>{{ $filter['label'] }}</span><i class="la la-check" aria-hidden="true"></i>
                            </button>
                        @endif
                        @foreach ($filter['options'] as $index => $option)
                            <button type="button" role="option" data-value="{{ $option['value'] }}"
                                    aria-selected="{{ $filter['id'] === 'bc-report-period' && $index === 0 ? 'true' : 'false' }}">
                                <span>{{ $option['label'] }}</span><i class="la la-check" aria-hidden="true"></i>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <button class="btn bc-btn-outline bc-report-refresh" id="bc-report-refresh" type="button">
                <i class="la la-sync-alt"></i> {{ __('bloodcare.reports.refresh') }}
            </button>
        </section>

        <nav class="bc-report-section-nav" role="tablist" aria-label="{{ __('bloodcare.reports.section_navigation') }}">
            @foreach ([
                ['key' => 'summary', 'icon' => 'la-th-large', 'label' => __('bloodcare.reports.section_summary')],
                ['key' => 'operations', 'icon' => 'la-tint', 'label' => __('bloodcare.reports.section_operations')],
                ['key' => 'people', 'icon' => 'la-user-check', 'label' => __('bloodcare.reports.section_people')],
                ['key' => 'centres', 'icon' => 'la-hospital', 'label' => __('bloodcare.reports.section_centres')],
                ['key' => 'insights', 'icon' => 'la-lightbulb', 'label' => __('bloodcare.reports.section_insights')],
            ] as $index => $section)
                <button class="{{ $index === 0 ? 'is-active' : '' }}" id="bc-report-tab-{{ $section['key'] }}"
                        type="button" role="tab"
                        aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                        aria-controls="bc-report-section-{{ $section['key'] }}"
                        data-bc-report-section-button="{{ $section['key'] }}">
                    <i class="la {{ $section['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $section['label'] }}</span>
                </button>
            @endforeach
        </nav>

        <section class="bc-report-section-panel" id="bc-report-section-summary" role="tabpanel"
                 aria-labelledby="bc-report-tab-summary" data-bc-report-section-panel="summary">
            <div class="bc-report-metrics" aria-label="{{ __('bloodcare.reports.summary') }}">
            @foreach ([
                ['key' => 'donors', 'icon' => 'la-users', 'tone' => 'blue', 'label' => __('bloodcare.reports.metric_donors'), 'help' => __('bloodcare.reports.metric_donors_help')],
                ['key' => 'donations', 'icon' => 'la-tint', 'tone' => 'red', 'label' => __('bloodcare.reports.metric_donations'), 'help' => __('bloodcare.reports.metric_donations_help')],
                ['key' => 'inventory', 'icon' => 'la-boxes', 'tone' => 'green', 'label' => __('bloodcare.reports.metric_inventory'), 'help' => __('bloodcare.reports.metric_inventory_help')],
                ['key' => 'completion', 'icon' => 'la-calendar-check', 'tone' => 'amber', 'label' => __('bloodcare.reports.metric_completion'), 'help' => __('bloodcare.reports.metric_completion_help')],
            ] as $metric)
                <article class="bc-report-metric">
                    <span class="bc-report-metric-icon bc-report-tone-{{ $metric['tone'] }}">
                        <i class="la {{ $metric['icon'] }}"></i>
                    </span>
                    <div>
                        <small>{{ $metric['label'] }}</small>
                        <strong data-report-metric="{{ $metric['key'] }}">—</strong>
                        <span>{{ $metric['help'] }}</span>
                    </div>
                </article>
            @endforeach
            </div>
        </section>

        <section class="bc-report-section-panel bc-report-grid" id="bc-report-section-operations" role="tabpanel"
                 aria-labelledby="bc-report-tab-operations" data-bc-report-section-panel="operations" hidden>
            <article class="bc-panel bc-report-panel bc-report-trend-panel">
                <header class="bc-report-panel-heading">
                    <div>
                        <h2>{{ __('bloodcare.reports.donation_trend_title') }}</h2>
                        <p>{{ __('bloodcare.reports.donation_trend_help') }}</p>
                    </div>
                    <span class="bc-report-panel-icon"><i class="la la-chart-line"></i></span>
                </header>
                <div class="bc-report-column-chart" id="bc-report-donation-chart"
                     role="img" aria-label="{{ __('bloodcare.reports.donation_trend_title') }}"></div>
            </article>

            <article class="bc-panel bc-report-panel">
                <header class="bc-report-panel-heading">
                    <div>
                        <h2>{{ __('bloodcare.reports.stock_title') }}</h2>
                        <p>{{ __('bloodcare.reports.stock_help') }}</p>
                    </div>
                    <span class="bc-report-panel-icon"><i class="la la-boxes"></i></span>
                </header>
                <div class="bc-report-stock-list" id="bc-report-stock-list"></div>
            </article>
        </section>

        <section class="bc-report-section-panel bc-report-grid" id="bc-report-section-people" role="tabpanel"
                 aria-labelledby="bc-report-tab-people" data-bc-report-section-panel="people" hidden>
            <article class="bc-panel bc-report-panel">
                <header class="bc-report-panel-heading">
                    <div>
                        <h2>{{ __('bloodcare.reports.donor_readiness_title') }}</h2>
                        <p>{{ __('bloodcare.reports.donor_readiness_help') }}</p>
                    </div>
                    <span class="bc-report-panel-icon"><i class="la la-heartbeat"></i></span>
                </header>
                <div class="bc-report-readiness">
                    <div class="bc-report-donut" id="bc-report-readiness-donut">
                        <span><strong id="bc-report-readiness-total">—</strong><small>{{ __('bloodcare.reports.donors') }}</small></span>
                    </div>
                    <div class="bc-report-legend" id="bc-report-readiness-legend"></div>
                </div>
            </article>

            <article class="bc-panel bc-report-panel">
                <header class="bc-report-panel-heading">
                    <div>
                        <h2>{{ __('bloodcare.reports.appointment_title') }}</h2>
                        <p>{{ __('bloodcare.reports.appointment_help') }}</p>
                    </div>
                    <span class="bc-report-panel-icon"><i class="la la-calendar-check"></i></span>
                </header>
                <div class="bc-report-outcome-list" id="bc-report-outcome-list"></div>
            </article>
        </section>

        <section class="bc-report-section-panel bc-report-grid" id="bc-report-section-centres" role="tabpanel"
                 aria-labelledby="bc-report-tab-centres" data-bc-report-section-panel="centres" hidden>
            <article class="bc-panel bc-report-panel bc-report-centre-panel">
                <header class="bc-report-panel-heading">
                    <div>
                        <h2>{{ __('bloodcare.reports.centre_title') }}</h2>
                        <p>{{ __('bloodcare.reports.centre_help') }}</p>
                    </div>
                    <span class="bc-report-panel-icon"><i class="la la-hospital"></i></span>
                </header>
                <div class="bc-report-centre-toolbar">
                    <label class="bc-filter-search bc-report-centre-search" for="bc-report-centre-search">
                        <i class="la la-search" aria-hidden="true"></i>
                        <input id="bc-report-centre-search" type="search"
                               placeholder="{{ __('bloodcare.reports.search_centres') }}"
                               aria-label="{{ __('bloodcare.reports.search_centres') }}" autocomplete="off">
                    </label>
                    <span id="bc-report-centre-count" aria-live="polite"></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter bc-report-table">
                        <thead>
                            <tr>
                                <th>{{ __('bloodcare.reports.centre') }}</th>
                                <th>{{ __('bloodcare.reports.bookings') }}</th>
                                <th>{{ __('bloodcare.reports.attended') }}</th>
                                <th>{{ __('bloodcare.reports.attendance_rate') }}</th>
                            </tr>
                        </thead>
                        <tbody id="bc-report-centre-body"></tbody>
                    </table>
                </div>
            </article>
        </section>

        <section class="bc-report-section-panel bc-report-grid" id="bc-report-section-insights" role="tabpanel"
                 aria-labelledby="bc-report-tab-insights" data-bc-report-section-panel="insights" hidden>
            <article class="bc-panel bc-report-panel bc-report-insight-panel">
                <header class="bc-report-panel-heading">
                    <div>
                        <h2>{{ __('bloodcare.reports.insights_title') }}</h2>
                        <p>{{ __('bloodcare.reports.insights_help') }}</p>
                    </div>
                    <span class="bc-report-panel-icon"><i class="la la-lightbulb"></i></span>
                </header>
                <div class="bc-report-insight-list" id="bc-report-insights"></div>
            </article>
        </section>

        <div class="bc-prototype-note">
            <i class="la la-info-circle"></i>
            <span>
                <strong>{{ __('bloodcare.reports.source_note_title') }}</strong>
                {{ __('bloodcare.reports.source_note_text') }}
            </span>
        </div>

        <script id="bc-report-config" type="application/json">@json($reportConfig)</script>

        <div class="bc-modal" id="bc-report-export-modal" hidden>
            <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.reports.cancel') }}"></button>
            <section class="bc-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="bc-report-export-title">
                <header class="bc-modal-header">
                    <div>
                        <p class="bc-eyebrow">{{ __('bloodcare.reports.export_eyebrow') }}</p>
                        <h2 id="bc-report-export-title">{{ __('bloodcare.reports.export_title') }}</h2>
                        <p>{{ __('bloodcare.reports.export_help') }}</p>
                    </div>
                    <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.reports.close') }}">
                        <i class="la la-times"></i>
                    </button>
                </header>
                <div class="bc-report-export-options">
                    <button type="button" data-report-output="csv">
                        <span class="bc-report-export-icon"><i class="la la-file-csv"></i></span>
                        <span><strong>{{ __('bloodcare.reports.csv_title') }}</strong><small>{{ __('bloodcare.reports.csv_help') }}</small></span>
                        <i class="la la-download"></i>
                    </button>
                    <button type="button" data-report-output="print">
                        <span class="bc-report-export-icon"><i class="la la-print"></i></span>
                        <span><strong>{{ __('bloodcare.reports.print_title') }}</strong><small>{{ __('bloodcare.reports.print_help') }}</small></span>
                        <i class="la la-arrow-right"></i>
                    </button>
                </div>
                <footer class="bc-modal-footer">
                    <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.reports.cancel') }}</button>
                </footer>
            </section>
        </div>

        <div class="bc-modal" id="bc-report-data-source-modal" hidden>
            <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.reports.close') }}"></button>
            <section class="bc-modal-dialog bc-modal-dialog-small" role="dialog" aria-modal="true" aria-labelledby="bc-report-data-source-title">
                <header class="bc-modal-header">
                    <div>
                        <p class="bc-eyebrow">{{ __('bloodcare.reports.data_source_eyebrow') }}</p>
                        <h2 id="bc-report-data-source-title">{{ __('bloodcare.reports.data_source_title') }}</h2>
                        <p>{{ __('bloodcare.reports.data_source_help') }}</p>
                    </div>
                    <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.reports.close') }}">
                        <i class="la la-times"></i>
                    </button>
                </header>
                <div class="bc-data-source-copy">
                    <span class="bc-data-source-icon"><i class="la la-project-diagram"></i></span>
                    <div>
                        <strong>{{ __('bloodcare.reports.source_note_title') }}</strong>
                        <p>{{ __('bloodcare.reports.data_source_body') }}</p>
                    </div>
                </div>
                <footer class="bc-modal-footer">
                    <button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.reports.close') }}</button>
                </footer>
            </section>
        </div>

        <div class="bc-toast" id="bc-report-toast" role="status" aria-live="polite" hidden>
            <i class="la la-check-circle"></i>
            <span></span>
        </div>
    </div>
@endsection
