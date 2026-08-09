@extends(backpack_view('blank'))

@section('title', $title)

@push('before_styles')
    @include('admin.partials.favicon')
@endpush

@push('after_styles')
    <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}?v={{ filemtime(public_path('css/bloodcare-admin.css')) }}">
@endpush

@php
    $interactiveModules = ['inventory', 'donors', 'cards', 'donations', 'appointments', 'history'];
    $interactionScripts = [
        'inventory' => 'js/bloodcare-inventory.js',
        'donors' => 'js/bloodcare-donors.js',
        'cards' => 'js/bloodcare-cards.js',
        'donations' => 'js/bloodcare-donations.js',
        'appointments' => 'js/bloodcare-appointments.js',
        'history' => 'js/bloodcare-history.js',
    ];
@endphp

@if (in_array($moduleKey, $interactiveModules, true))
    @push('after_scripts')
        @php $interactionScript = $interactionScripts[$moduleKey]; @endphp
        @if (in_array($moduleKey, ['cards', 'inventory'], true))
            <script src="{{ asset('vendor/bloodcare-qrcode/qrcode.js') }}?v={{ filemtime(public_path('vendor/bloodcare-qrcode/qrcode.js')) }}" defer></script>
        @endif
        <script src="{{ asset($interactionScript) }}?v={{ filemtime(public_path($interactionScript)) }}" defer></script>
    @endpush
@endif

@section('content')
    <div class="bc-admin-page bc-module-page" data-bc-module="{{ $moduleKey }}">
        <header class="bc-page-heading">
            <div class="bc-module-title">
                <span class="bc-module-icon"><i class="la {{ $icon }}"></i></span>
                <div>
                    <p class="bc-eyebrow">{{ $eyebrow }}</p>
                    <h1>{{ $title }}</h1>
                    <p>{{ $description }}</p>
                </div>
            </div>
            <div class="bc-heading-tools">
                @include('admin.partials.utility-controls')
                <div class="bc-heading-actions">
                    @if (in_array($moduleKey, $interactiveModules, true))
                        <button class="bc-prototype-badge" id="bc-data-source-button" type="button">
                            <i class="la {{ in_array($moduleKey, ['inventory', 'donors', 'cards', 'donations', 'appointments', 'history'], true) ? 'la-database' : 'la-drafting-compass' }}"></i>
                            {{ __(in_array($moduleKey, ['inventory', 'donors', 'cards', 'donations', 'appointments', 'history'], true) ? 'bloodcare.module.database_live' : 'bloodcare.module.ui_preview') }}
                        </button>
                    @else
                        <span class="bc-prototype-badge"><i class="la la-drafting-compass"></i> {{ __('bloodcare.module.ui_preview') }}</span>
                    @endif
                    @if ($primaryAction)
                        @php $moduleIsInteractive = in_array($moduleKey, $interactiveModules, true); @endphp
                        @if ($moduleKey === 'donors')
                            <button class="btn bc-btn-primary" id="bc-donor-register-choice-button" type="button"
                                    data-bc-open-registration-choice aria-haspopup="dialog" aria-controls="bc-donor-registration-choice">
                                <i class="la la-plus"></i> {{ $primaryAction }}
                            </button>
                        @else
                            <button class="btn bc-btn-primary" id="{{ $moduleIsInteractive ? 'bc-primary-action' : '' }}"
                                    type="button" @disabled(!$moduleIsInteractive)
                                    title="{{ $moduleIsInteractive ? $primaryAction : __('bloodcare.module.action_unavailable') }}">
                                <i class="la la-plus"></i> {{ $primaryAction }}
                            </button>
                        @endif
                    @endif
                </div>
            </div>
        </header>

        <section class="bc-module-metrics">
            @foreach ($metrics as $index => $metric)
                <article class="bc-mini-metric"
                    @if ($moduleKey === 'inventory') data-inventory-metric="{{ ['available', 'reserved', 'expiring'][$index] }}"
                    @elseif ($moduleKey === 'donors') data-donor-metric="{{ ['total', 'eligible', 'review'][$index] }}"
                    @elseif ($moduleKey === 'cards') data-card-metric="{{ ['active', 'pending', 'expiring'][$index] }}"
                    @elseif ($moduleKey === 'donations') data-donation-metric="{{ ['month', 'accepted', 'review'][$index] }}"
                    @elseif ($moduleKey === 'appointments') data-appointment-metric="{{ ['today', 'checkedIn', 'pending'][$index] }}"
                    @elseif ($moduleKey === 'history') data-history-metric="{{ ['today', 'staff', 'inventory'][$index] }}"
                    @endif>
                    <span class="bc-mini-dot bc-dot-{{ $metric['tone'] }}"></span>
                    <div><small>{{ $metric['label'] }}</small><strong>{{ $metric['value'] }}</strong></div>
                </article>
            @endforeach
        </section>

        @isset($bloodGroups)
            <section class="bc-panel bc-module-inventory">
                <div class="bc-panel-heading">
                    <div><h2>{{ __('bloodcare.module.stock_title') }}</h2><p>{{ __('bloodcare.module.stock_subtitle') }}</p></div>
                </div>
                <div class="bc-blood-grid">
                    @foreach ($bloodGroups as $blood)
                        @php $tone = $blood['status']; @endphp
                        <article class="bc-blood-card bc-stock-{{ $tone }}"
                                 @if ($moduleKey === 'inventory')
                                     data-blood-group="{{ str_replace('−', '-', $blood['group']) }}"
                                     role="button" tabindex="0"
                                     aria-label="{{ __('bloodcare.inventory.filter_group', ['group' => $blood['group']]) }}"
                                 @endif>
                            <div class="bc-blood-card-top">
                                <strong>{{ $blood['group'] }}</strong>
                                <span>{{ __('bloodcare.dashboard.status.'.$blood['status']) }}</span>
                            </div>
                            <div class="bc-blood-units">{{ $blood['units'] }} <small>{{ __('bloodcare.module.units') }}</small></div>
                            <div class="bc-stock-bar"><span style="width: {{ $blood['percent'] }}%"></span></div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endisset

        <section class="bc-panel bc-module-table-panel">
            @if ($moduleKey === 'inventory')
                <div class="bc-filter-bar" aria-label="{{ __('bloodcare.inventory.filters') }}">
                    <label class="bc-filter-search" for="bc-inventory-search">
                        <i class="la la-search"></i>
                        <input id="bc-inventory-search" type="search" placeholder="{{ $filters[0] }}"
                               autocomplete="off">
                    </label>
                    <div class="bc-filter-dropdown" data-bc-select>
                        <i class="la la-filter bc-filter-dropdown-icon" aria-hidden="true"></i>
                        <select class="visually-hidden" id="bc-inventory-status" aria-label="{{ $filters[1] }}"
                                tabindex="-1" aria-hidden="true">
                            <option value="all">{{ $filters[1] }}</option>
                            <option value="Available">{{ __('bloodcare.dashboard.status.available') }}</option>
                            <option value="Reserved">{{ __('bloodcare.dashboard.status.reserved') }}</option>
                            <option value="Quarantined">{{ __('bloodcare.dashboard.status.quarantined') }}</option>
                            <option value="Expiring">{{ __('bloodcare.dashboard.status.expiring') }}</option>
                            <option value="Expired">{{ __('bloodcare.dashboard.status.expired') }}</option>
                            <option value="Used">{{ __('bloodcare.dashboard.status.used') }}</option>
                            <option value="Discarded">{{ __('bloodcare.dashboard.status.discarded') }}</option>
                        </select>
                        <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox"
                                aria-expanded="false" aria-controls="bc-inventory-status-menu">
                            <span data-bc-select-label>{{ $filters[1] }}</span>
                            <i class="la la-angle-down" aria-hidden="true"></i>
                        </button>
                        <div class="bc-filter-dropdown-menu" id="bc-inventory-status-menu" role="listbox"
                             aria-label="{{ $filters[1] }}" hidden>
                            <button type="button" role="option" data-value="all" aria-selected="true">
                                <span>{{ $filters[1] }}</span><i class="la la-check" aria-hidden="true"></i>
                            </button>
                            <button type="button" role="option" data-value="Available" aria-selected="false">
                                <span>{{ __('bloodcare.dashboard.status.available') }}</span><i class="la la-check" aria-hidden="true"></i>
                            </button>
                            <button type="button" role="option" data-value="Reserved" aria-selected="false">
                                <span>{{ __('bloodcare.dashboard.status.reserved') }}</span><i class="la la-check" aria-hidden="true"></i>
                            </button>
                            <button type="button" role="option" data-value="Quarantined" aria-selected="false">
                                <span>{{ __('bloodcare.dashboard.status.quarantined') }}</span><i class="la la-check" aria-hidden="true"></i>
                            </button>
                            <button type="button" role="option" data-value="Expiring" aria-selected="false">
                                <span>{{ __('bloodcare.dashboard.status.expiring') }}</span><i class="la la-check" aria-hidden="true"></i>
                            </button>
                            <button type="button" role="option" data-value="Expired" aria-selected="false">
                                <span>{{ __('bloodcare.dashboard.status.expired') }}</span><i class="la la-check" aria-hidden="true"></i>
                            </button>
                            <button type="button" role="option" data-value="Used" aria-selected="false">
                                <span>{{ __('bloodcare.dashboard.status.used') }}</span><i class="la la-check" aria-hidden="true"></i>
                            </button>
                            <button type="button" role="option" data-value="Discarded" aria-selected="false">
                                <span>{{ __('bloodcare.dashboard.status.discarded') }}</span><i class="la la-check" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="bc-filter-dropdown" data-bc-select>
                        <i class="la la-calendar bc-filter-dropdown-icon" aria-hidden="true"></i>
                        <select class="visually-hidden" id="bc-inventory-expiry" aria-label="{{ $filters[2] }}"
                                tabindex="-1" aria-hidden="true">
                            <option value="all">{{ $filters[2] }}</option>
                            <option value="7">{{ __('bloodcare.inventory.next_7_days') }}</option>
                            <option value="30">{{ __('bloodcare.inventory.next_30_days') }}</option>
                            <option value="expired">{{ __('bloodcare.inventory.already_expired') }}</option>
                        </select>
                        <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox"
                                aria-expanded="false" aria-controls="bc-inventory-expiry-menu">
                            <span data-bc-select-label>{{ $filters[2] }}</span>
                            <i class="la la-angle-down" aria-hidden="true"></i>
                        </button>
                        <div class="bc-filter-dropdown-menu" id="bc-inventory-expiry-menu" role="listbox"
                             aria-label="{{ $filters[2] }}" hidden>
                            <button type="button" role="option" data-value="all" aria-selected="true">
                                <span>{{ $filters[2] }}</span><i class="la la-check" aria-hidden="true"></i>
                            </button>
                            <button type="button" role="option" data-value="7" aria-selected="false">
                                <span>{{ __('bloodcare.inventory.next_7_days') }}</span><i class="la la-check" aria-hidden="true"></i>
                            </button>
                            <button type="button" role="option" data-value="30" aria-selected="false">
                                <span>{{ __('bloodcare.inventory.next_30_days') }}</span><i class="la la-check" aria-hidden="true"></i>
                            </button>
                            <button type="button" role="option" data-value="expired" aria-selected="false">
                                <span>{{ __('bloodcare.inventory.already_expired') }}</span><i class="la la-check" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @elseif ($moduleKey === 'donors')
                <div class="bc-filter-bar" aria-label="{{ __('bloodcare.donors.filters') }}">
                    <label class="bc-filter-search" for="bc-donor-search">
                        <i class="la la-search"></i>
                        <input id="bc-donor-search" type="search" placeholder="{{ $filters[0] }}"
                               autocomplete="off">
                    </label>

                    @php
                        $donorFilterMenus = [
                            [
                                'id' => 'bc-donor-group',
                                'icon' => 'la-tint',
                                'label' => __('bloodcare.donors.all_blood_groups'),
                                'options' => collect(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])
                                    ->map(fn ($group) => ['value' => $group, 'label' => $group])->all(),
                            ],
                            [
                                'id' => 'bc-donor-eligibility',
                                'icon' => 'la-heartbeat',
                                'label' => __('bloodcare.donors.all_eligibility'),
                                'options' => [
                                    ['value' => 'Eligible', 'label' => __('bloodcare.donors.eligible')],
                                    ['value' => 'Review', 'label' => __('bloodcare.donors.review')],
                                    ['value' => 'Deferred', 'label' => __('bloodcare.donors.deferred')],
                                ],
                            ],
                            [
                                'id' => 'bc-donor-status',
                                'icon' => 'la-user-check',
                                'label' => __('bloodcare.donors.all_statuses'),
                                'options' => [
                                    ['value' => 'Active', 'label' => __('bloodcare.dashboard.status.active')],
                                    ['value' => 'Pending', 'label' => __('bloodcare.dashboard.status.pending')],
                                    ['value' => 'Inactive', 'label' => __('bloodcare.donors.inactive')],
                                ],
                            ],
                        ];
                    @endphp

                    @foreach ($donorFilterMenus as $menu)
                        <div class="bc-filter-dropdown" data-bc-select>
                            <i class="la {{ $menu['icon'] }} bc-filter-dropdown-icon" aria-hidden="true"></i>
                            <select class="visually-hidden" id="{{ $menu['id'] }}" aria-label="{{ $menu['label'] }}"
                                    tabindex="-1" aria-hidden="true">
                                <option value="all">{{ $menu['label'] }}</option>
                                @foreach ($menu['options'] as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox"
                                    aria-expanded="false" aria-controls="{{ $menu['id'] }}-menu">
                                <span data-bc-select-label>{{ $menu['label'] }}</span>
                                <i class="la la-angle-down" aria-hidden="true"></i>
                            </button>
                            <div class="bc-filter-dropdown-menu" id="{{ $menu['id'] }}-menu" role="listbox"
                                 aria-label="{{ $menu['label'] }}" hidden>
                                <button type="button" role="option" data-value="all" aria-selected="true">
                                    <span>{{ $menu['label'] }}</span><i class="la la-check" aria-hidden="true"></i>
                                </button>
                                @foreach ($menu['options'] as $option)
                                    <button type="button" role="option" data-value="{{ $option['value'] }}" aria-selected="false">
                                        <span>{{ $option['label'] }}</span><i class="la la-check" aria-hidden="true"></i>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif ($moduleKey === 'cards')
                <div class="bc-filter-bar" aria-label="{{ __('bloodcare.cards.filters') }}">
                    <label class="bc-filter-search" for="bc-card-search">
                        <i class="la la-search"></i>
                        <input id="bc-card-search" type="search" placeholder="{{ $filters[0] }}"
                               autocomplete="off">
                    </label>

                    @php
                        $cardFilterMenus = [
                            [
                                'id' => 'bc-card-group',
                                'icon' => 'la-tint',
                                'label' => __('bloodcare.cards.all_blood_groups'),
                                'options' => collect(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])
                                    ->map(fn ($group) => ['value' => $group, 'label' => $group])->all(),
                            ],
                            [
                                'id' => 'bc-card-status',
                                'icon' => 'la-id-card',
                                'label' => __('bloodcare.cards.all_statuses'),
                                'options' => [
                                    ['value' => 'Active', 'label' => __('bloodcare.dashboard.status.active')],
                                    ['value' => 'Pending', 'label' => __('bloodcare.dashboard.status.pending')],
                                    ['value' => 'Expiring', 'label' => __('bloodcare.dashboard.status.expiring')],
                                    ['value' => 'Expired', 'label' => __('bloodcare.dashboard.status.expired')],
                                    ['value' => 'Suspended', 'label' => __('bloodcare.cards.suspended')],
                                ],
                            ],
                            [
                                'id' => 'bc-card-issued',
                                'icon' => 'la-calendar',
                                'label' => __('bloodcare.cards.all_issue_dates'),
                                'options' => [
                                    ['value' => 'month', 'label' => __('bloodcare.cards.issued_this_month')],
                                    ['value' => 'year', 'label' => __('bloodcare.cards.issued_this_year')],
                                    ['value' => 'older', 'label' => __('bloodcare.cards.issued_earlier')],
                                    ['value' => 'pending', 'label' => __('bloodcare.cards.not_issued')],
                                ],
                            ],
                        ];
                    @endphp

                    @foreach ($cardFilterMenus as $menu)
                        <div class="bc-filter-dropdown" data-bc-select>
                            <i class="la {{ $menu['icon'] }} bc-filter-dropdown-icon" aria-hidden="true"></i>
                            <select class="visually-hidden" id="{{ $menu['id'] }}" aria-label="{{ $menu['label'] }}"
                                    tabindex="-1" aria-hidden="true">
                                <option value="all">{{ $menu['label'] }}</option>
                                @foreach ($menu['options'] as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox"
                                    aria-expanded="false" aria-controls="{{ $menu['id'] }}-menu">
                                <span data-bc-select-label>{{ $menu['label'] }}</span>
                                <i class="la la-angle-down" aria-hidden="true"></i>
                            </button>
                            <div class="bc-filter-dropdown-menu" id="{{ $menu['id'] }}-menu" role="listbox"
                                 aria-label="{{ $menu['label'] }}" hidden>
                                <button type="button" role="option" data-value="all" aria-selected="true">
                                    <span>{{ $menu['label'] }}</span><i class="la la-check" aria-hidden="true"></i>
                                </button>
                                @foreach ($menu['options'] as $option)
                                    <button type="button" role="option" data-value="{{ $option['value'] }}" aria-selected="false">
                                        <span>{{ $option['label'] }}</span><i class="la la-check" aria-hidden="true"></i>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif ($moduleKey === 'donations')
                <div class="bc-filter-bar" aria-label="{{ __('bloodcare.donations.filters') }}">
                    <label class="bc-filter-search" for="bc-donation-search">
                        <i class="la la-search"></i>
                        <input id="bc-donation-search" type="search" placeholder="{{ $filters[0] }}"
                               autocomplete="off">
                    </label>

                    @php
                        $donationFilterMenus = [
                            [
                                'id' => 'bc-donation-group',
                                'icon' => 'la-tint',
                                'label' => __('bloodcare.donations.all_blood_groups'),
                                'options' => collect(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])
                                    ->map(fn ($group) => ['value' => $group, 'label' => $group])->all(),
                            ],
                            [
                                'id' => 'bc-donation-status',
                                'icon' => 'la-clipboard-check',
                                'label' => __('bloodcare.donations.all_results'),
                                'options' => [
                                    ['value' => 'Accepted', 'label' => __('bloodcare.donations.accepted')],
                                    ['value' => 'Screening', 'label' => __('bloodcare.donations.screening')],
                                    ['value' => 'Rejected', 'label' => __('bloodcare.donations.rejected')],
                                ],
                            ],
                            [
                                'id' => 'bc-donation-date',
                                'icon' => 'la-calendar',
                                'label' => __('bloodcare.donations.all_dates'),
                                'options' => [
                                    ['value' => 'today', 'label' => __('bloodcare.donations.today')],
                                    ['value' => 'month', 'label' => __('bloodcare.donations.this_month')],
                                    ['value' => 'earlier', 'label' => __('bloodcare.donations.earlier')],
                                ],
                            ],
                        ];
                    @endphp

                    @foreach ($donationFilterMenus as $menu)
                        <div class="bc-filter-dropdown" data-bc-select>
                            <i class="la {{ $menu['icon'] }} bc-filter-dropdown-icon" aria-hidden="true"></i>
                            <select class="visually-hidden" id="{{ $menu['id'] }}" aria-label="{{ $menu['label'] }}"
                                    tabindex="-1" aria-hidden="true">
                                <option value="all">{{ $menu['label'] }}</option>
                                @foreach ($menu['options'] as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox"
                                    aria-expanded="false" aria-controls="{{ $menu['id'] }}-menu">
                                <span data-bc-select-label>{{ $menu['label'] }}</span>
                                <i class="la la-angle-down" aria-hidden="true"></i>
                            </button>
                            <div class="bc-filter-dropdown-menu" id="{{ $menu['id'] }}-menu" role="listbox"
                                 aria-label="{{ $menu['label'] }}" hidden>
                                <button type="button" role="option" data-value="all" aria-selected="true">
                                    <span>{{ $menu['label'] }}</span><i class="la la-check" aria-hidden="true"></i>
                                </button>
                                @foreach ($menu['options'] as $option)
                                    <button type="button" role="option" data-value="{{ $option['value'] }}" aria-selected="false">
                                        <span>{{ $option['label'] }}</span><i class="la la-check" aria-hidden="true"></i>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <button class="bc-filter-export" id="bc-donation-export" type="button">
                        <i class="la la-file-export"></i> {{ __('bloodcare.donations.export_csv') }}
                    </button>
                </div>
            @elseif ($moduleKey === 'appointments')
                <div class="bc-filter-bar" aria-label="{{ __('bloodcare.appointments.filters') }}">
                    <label class="bc-filter-search" for="bc-appointment-search">
                        <i class="la la-search"></i>
                        <input id="bc-appointment-search" type="search" placeholder="{{ $filters[0] }}"
                               autocomplete="off">
                    </label>

                    @php
                        $appointmentFilterMenus = [
                            [
                                'id' => 'bc-appointment-status',
                                'icon' => 'la-clipboard-check',
                                'label' => __('bloodcare.appointments.all_statuses'),
                                'options' => [
                                    ['value' => 'Pending', 'label' => __('bloodcare.appointments.pending')],
                                    ['value' => 'Confirmed', 'label' => __('bloodcare.appointments.confirmed')],
                                    ['value' => 'Checked in', 'label' => __('bloodcare.appointments.checked_in')],
                                    ['value' => 'Completed', 'label' => __('bloodcare.appointments.completed')],
                                    ['value' => 'Cancelled', 'label' => __('bloodcare.appointments.cancelled')],
                                    ['value' => 'No-show', 'label' => __('bloodcare.appointments.no_show')],
                                ],
                            ],
                            [
                                'id' => 'bc-appointment-centre',
                                'icon' => 'la-map-marker',
                                'label' => __('bloodcare.appointments.all_centres'),
                                'options' => collect($appointmentData['centres'])
                                    ->map(fn ($centre) => ['value' => $centre, 'label' => $centre])->all(),
                            ],
                            [
                                'id' => 'bc-appointment-date',
                                'icon' => 'la-calendar',
                                'label' => __('bloodcare.appointments.all_dates'),
                                'options' => [
                                    ['value' => 'today', 'label' => __('bloodcare.appointments.today')],
                                    ['value' => 'upcoming', 'label' => __('bloodcare.appointments.upcoming')],
                                    ['value' => 'past', 'label' => __('bloodcare.appointments.past')],
                                ],
                            ],
                        ];
                    @endphp

                    @foreach ($appointmentFilterMenus as $menu)
                        <div class="bc-filter-dropdown" data-bc-select>
                            <i class="la {{ $menu['icon'] }} bc-filter-dropdown-icon" aria-hidden="true"></i>
                            <select class="visually-hidden" id="{{ $menu['id'] }}" aria-label="{{ $menu['label'] }}"
                                    tabindex="-1" aria-hidden="true">
                                <option value="all">{{ $menu['label'] }}</option>
                                @foreach ($menu['options'] as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox"
                                    aria-expanded="false" aria-controls="{{ $menu['id'] }}-menu">
                                <span data-bc-select-label>{{ $menu['label'] }}</span>
                                <i class="la la-angle-down" aria-hidden="true"></i>
                            </button>
                            <div class="bc-filter-dropdown-menu" id="{{ $menu['id'] }}-menu" role="listbox"
                                 aria-label="{{ $menu['label'] }}" hidden>
                                <button type="button" role="option" data-value="all" aria-selected="true">
                                    <span>{{ $menu['label'] }}</span><i class="la la-check" aria-hidden="true"></i>
                                </button>
                                @foreach ($menu['options'] as $option)
                                    <button type="button" role="option" data-value="{{ $option['value'] }}" aria-selected="false">
                                        <span>{{ $option['label'] }}</span><i class="la la-check" aria-hidden="true"></i>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="bc-appointment-toolbar">
                    <div class="bc-appointment-view-switch" role="group" aria-label="{{ __('bloodcare.appointments.calendar_view') }}">
                        <button class="active" id="bc-appointment-list-button" type="button" aria-pressed="true">
                            <i class="la la-list"></i> {{ __('bloodcare.appointments.list_view') }}
                        </button>
                        <button id="bc-appointment-calendar-button" type="button" aria-pressed="false">
                            <i class="la la-calendar"></i> {{ __('bloodcare.appointments.calendar_view') }}
                        </button>
                    </div>
                    <button class="bc-manage-centres-button" id="bc-manage-centres-button" type="button">
                        <i class="la la-hospital"></i>
                        {{ __('bloodcare.appointments.manage_centres') }}
                    </button>
                    <div class="bc-calendar-navigation" id="bc-calendar-navigation" hidden>
                        <button type="button" id="bc-calendar-previous" aria-label="{{ __('bloodcare.appointments.previous_month') }}">
                            <i class="la la-angle-left"></i>
                        </button>
                        <strong id="bc-calendar-month"></strong>
                        <button type="button" id="bc-calendar-next" aria-label="{{ __('bloodcare.appointments.next_month') }}">
                            <i class="la la-angle-right"></i>
                        </button>
                        <button class="bc-calendar-today" type="button" id="bc-calendar-today">
                            {{ __('bloodcare.appointments.today') }}
                        </button>
                    </div>
                </div>
            @elseif ($moduleKey === 'history')
                <div class="bc-filter-bar bc-history-filter-bar" aria-label="{{ __('bloodcare.history.filters') }}">
                    <label class="bc-filter-search" for="bc-history-search">
                        <i class="la la-search"></i>
                        <input id="bc-history-search" type="search" placeholder="{{ $filters[0] }}"
                               autocomplete="off">
                    </label>

                    @php
                        $historyStaff = collect($historyData['records'])
                            ->pluck('staff')
                            ->push($historyData['staffName'])
                            ->filter()
                            ->unique()
                            ->sort()
                            ->values();
                        $historyFilterMenus = [
                            [
                                'id' => 'bc-history-type',
                                'icon' => 'la-layer-group',
                                'label' => __('bloodcare.history.all_activity_types'),
                                'options' => [
                                    ['value' => 'Donor', 'label' => __('bloodcare.history.type_donor')],
                                    ['value' => 'Card', 'label' => __('bloodcare.history.type_card')],
                                    ['value' => 'Donation', 'label' => __('bloodcare.history.type_donation')],
                                    ['value' => 'Inventory', 'label' => __('bloodcare.history.type_inventory')],
                                    ['value' => 'Appointment', 'label' => __('bloodcare.history.type_appointment')],
                                    ['value' => 'Screening', 'label' => __('bloodcare.history.type_screening')],
                                ],
                            ],
                            [
                                'id' => 'bc-history-staff',
                                'icon' => 'la-user-shield',
                                'label' => __('bloodcare.history.all_staff'),
                                'options' => $historyStaff
                                    ->map(fn ($staff) => ['value' => $staff, 'label' => $staff])
                                    ->all(),
                            ],
                            [
                                'id' => 'bc-history-date',
                                'icon' => 'la-calendar',
                                'label' => __('bloodcare.history.all_dates'),
                                'options' => [
                                    ['value' => 'today', 'label' => __('bloodcare.history.today')],
                                    ['value' => '7', 'label' => __('bloodcare.history.last_7_days')],
                                    ['value' => '30', 'label' => __('bloodcare.history.last_30_days')],
                                ],
                            ],
                        ];
                    @endphp

                    @foreach ($historyFilterMenus as $menu)
                        <div class="bc-filter-dropdown" data-bc-select>
                            <i class="la {{ $menu['icon'] }} bc-filter-dropdown-icon" aria-hidden="true"></i>
                            <select class="visually-hidden" id="{{ $menu['id'] }}" aria-label="{{ $menu['label'] }}"
                                    tabindex="-1" aria-hidden="true">
                                <option value="all">{{ $menu['label'] }}</option>
                                @foreach ($menu['options'] as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox"
                                    aria-expanded="false" aria-controls="{{ $menu['id'] }}-menu">
                                <span data-bc-select-label>{{ $menu['label'] }}</span>
                                <i class="la la-angle-down" aria-hidden="true"></i>
                            </button>
                            <div class="bc-filter-dropdown-menu" id="{{ $menu['id'] }}-menu" role="listbox"
                                 aria-label="{{ $menu['label'] }}" hidden>
                                <button type="button" role="option" data-value="all" aria-selected="true">
                                    <span>{{ $menu['label'] }}</span><i class="la la-check" aria-hidden="true"></i>
                                </button>
                                @foreach ($menu['options'] as $option)
                                    <button type="button" role="option" data-value="{{ $option['value'] }}" aria-selected="false">
                                        <span>{{ $option['label'] }}</span><i class="la la-check" aria-hidden="true"></i>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <button class="bc-filter-export" id="bc-history-export" type="button">
                        <i class="la la-file-export"></i> {{ __('bloodcare.history.export_csv') }}
                    </button>
                </div>
            @else
                <div class="bc-filter-bar">
                    @foreach ($filters as $index => $filter)
                        <button type="button" disabled>
                            <i class="la {{ $index === 0 ? 'la-search' : 'la-filter' }}"></i>
                            {{ $filter }}
                            @if ($index > 0)<i class="la la-angle-down"></i>@endif
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="table-responsive {{ $moduleKey === 'history' ? 'bc-history-table-scroll' : '' }}"
                 @if ($moduleKey === 'appointments') id="bc-appointment-list-view" @endif
                 @if ($moduleKey === 'history') tabindex="0" role="region" aria-label="{{ $title }}" @endif>
                <table class="table bc-table bc-module-table">
                    <thead>
                        <tr>
                            @foreach ($columns as $column)
                                <th>{{ $column }}</th>
                            @endforeach
                            <th class="text-end">{{ __('bloodcare.module.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody
                        @if ($moduleKey === 'inventory') id="bc-inventory-table-body"
                        @elseif ($moduleKey === 'donors') id="bc-donor-table-body"
                        @elseif ($moduleKey === 'cards') id="bc-card-table-body"
                        @elseif ($moduleKey === 'donations') id="bc-donation-table-body"
                        @elseif ($moduleKey === 'appointments') id="bc-appointment-table-body"
                        @elseif ($moduleKey === 'history') id="bc-history-table-body"
                        @endif>
                        @if ($moduleKey === 'inventory')
                            @foreach (array_slice($inventoryData['records'], 0, 5) as $row)
                                <tr>
                                    <td><strong>{{ $row['id'] }}</strong></td>
                                    <td><span class="bc-group-badge">{{ $row['group'] }}</span></td>
                                    <td>{{ \Carbon\Carbon::parse($row['collected'])->format('d M Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($row['expires'])->format('d M Y') }}</td>
                                    <td>{{ $row['location'] }}</td>
                                    <td><span class="bc-status bc-status-{{ str($row['status'])->slug() }}">{{ __('bloodcare.dashboard.status.'.str($row['status'])->slug('_')) }}</span></td>
                                    <td class="text-end">
                                        <button class="bc-row-action" type="button" data-unit-menu="{{ $row['id'] }}"
                                                aria-label="{{ __('bloodcare.inventory.open_actions', ['unit' => $row['id']]) }}">
                                            <i class="la la-ellipsis-h"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @elseif ($moduleKey === 'donors')
                            @foreach (array_slice($donorData['records'], 0, 5) as $row)
                                <tr>
                                    <td><strong>{{ $row['id'] }}</strong></td>
                                    <td>
                                        <span class="bc-donor-cell">
                                            <span class="bc-donor-table-avatar">{{ mb_strtoupper(mb_substr($row['name'], 0, 1, 'UTF-8'), 'UTF-8') }}</span>
                                            <span>{{ $row['name'] }}</span>
                                        </span>
                                    </td>
                                    <td><span class="bc-group-badge">{{ $row['group'] }}</span></td>
                                    <td>{{ $row['lastDonation'] ? \Carbon\Carbon::parse($row['lastDonation'])->format('d M Y') : __('bloodcare.donors.not_recorded') }}</td>
                                    <td>
                                        @if ($row['nextEligible'] === 'now')
                                            {{ __('bloodcare.donors.eligible_now') }}
                                        @elseif ($row['nextEligible'] === 'review')
                                            {{ __('bloodcare.donors.pending_review') }}
                                        @else
                                            {{ \Carbon\Carbon::parse($row['nextEligible'])->format('d M Y') }}
                                        @endif
                                    </td>
                                    <td><span class="bc-status bc-status-{{ str($row['status'])->slug() }}">{{ __('bloodcare.dashboard.status.'.str($row['status'])->slug('_')) }}</span></td>
                                    <td class="text-end">
                                        <button class="bc-row-action" type="button" data-donor-menu="{{ $row['id'] }}"
                                                aria-label="{{ __('bloodcare.donors.open_actions', ['donor' => $row['name']]) }}">
                                            <i class="la la-ellipsis-h"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @elseif ($moduleKey === 'cards')
                            @foreach (array_slice($cardData['records'], 0, 5) as $row)
                                <tr>
                                    <td><strong>{{ $row['cardNumber'] }}</strong></td>
                                    <td>
                                        <span class="bc-donor-cell">
                                            <span class="bc-donor-table-avatar">{{ mb_strtoupper(mb_substr($row['donorName'], 0, 1, 'UTF-8'), 'UTF-8') }}</span>
                                            <span>{{ $row['donorName'] }}</span>
                                        </span>
                                    </td>
                                    <td><span class="bc-group-badge">{{ $row['group'] }}</span></td>
                                    <td>{{ $row['issueDate'] ? \Carbon\Carbon::parse($row['issueDate'])->format('d M Y') : __('bloodcare.cards.not_issued') }}</td>
                                    <td>{{ $row['expiryDate'] ? \Carbon\Carbon::parse($row['expiryDate'])->format('d M Y') : '—' }}</td>
                                    <td><span class="bc-status bc-status-{{ str($row['status'])->slug() }}">{{ $row['status'] === 'Suspended' ? __('bloodcare.cards.suspended') : __('bloodcare.dashboard.status.'.str($row['status'])->slug('_')) }}</span></td>
                                    <td class="text-end">
                                        <button class="bc-row-action" type="button" data-card-menu="{{ $row['cardNumber'] }}"
                                                aria-label="{{ __('bloodcare.cards.open_actions', ['donor' => $row['donorName']]) }}">
                                            <i class="la la-ellipsis-h"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @elseif ($moduleKey === 'donations')
                            @foreach (array_slice($donationData['records'], 0, 5) as $row)
                                <tr>
                                    <td><strong>{{ $row['id'] }}</strong></td>
                                    <td>
                                        <span class="bc-donor-cell">
                                            <span class="bc-donor-table-avatar">{{ mb_strtoupper(mb_substr($row['donorName'], 0, 1, 'UTF-8'), 'UTF-8') }}</span>
                                            <span>{{ $row['donorName'] }}</span>
                                        </span>
                                    </td>
                                    <td><span class="bc-group-badge">{{ $row['group'] }}</span></td>
                                    <td>{{ $row['quantity'] }} ml</td>
                                    <td>{{ \Carbon\Carbon::parse($row['donationDate'])->format('d M Y') }}</td>
                                    <td><span class="bc-status bc-status-{{ str($row['status'])->slug() }}">{{ __('bloodcare.donations.'.str($row['status'])->slug('_')) }}</span></td>
                                    <td class="text-end">
                                        <button class="bc-row-action" type="button" data-donation-menu="{{ $row['id'] }}"
                                                aria-label="{{ __('bloodcare.donations.open_actions', ['donation' => $row['id']]) }}">
                                            <i class="la la-ellipsis-h"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @elseif ($moduleKey === 'appointments')
                            @foreach (array_slice($appointmentData['records'], 0, 5) as $row)
                                <tr>
                                    <td><strong>{{ $row['id'] }}</strong></td>
                                    <td>
                                        <span class="bc-donor-cell">
                                            <span class="bc-donor-table-avatar">{{ mb_strtoupper(mb_substr($row['donorName'], 0, 1, 'UTF-8'), 'UTF-8') }}</span>
                                            <span>{{ $row['donorName'] }}</span>
                                        </span>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($row['date'].' '.$row['time'])->format('H:i') }}</td>
                                    <td>{{ $row['centre'] }}</td>
                                    <td><span class="bc-status bc-status-{{ str($row['status'])->slug() }}">{{ __('bloodcare.appointments.'.str($row['status'])->slug('_')) }}</span></td>
                                    <td class="text-end">
                                        <button class="bc-row-action" type="button" data-appointment-menu="{{ $row['id'] }}"
                                                aria-label="{{ __('bloodcare.appointments.open_actions', ['appointment' => $row['id']]) }}">
                                            <i class="la la-ellipsis-h"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @elseif ($moduleKey === 'history')
                            @foreach (array_slice($historyData['records'], 0, 5) as $row)
                                <tr>
                                    <td>
                                        <span class="bc-history-time">
                                            <strong>{{ \Carbon\Carbon::parse($row['dateTime'])->format('H:i') }}</strong>
                                            <small>{{ \Carbon\Carbon::parse($row['dateTime'])->format('d M Y') }}</small>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="bc-history-activity">
                                            <span class="bc-history-type-icon bc-history-type-{{ str($row['type'])->slug() }}">
                                                <i class="la la-history"></i>
                                            </span>
                                            <span>
                                                <strong>{{ $row['action'] }}</strong>
                                                <small>{{ $row['type'] }}</small>
                                            </span>
                                        </span>
                                    </td>
                                    <td><strong>{{ $row['reference'] }}</strong></td>
                                    <td>{{ $row['staff'] }}</td>
                                    <td><span class="bc-history-detail-preview">{{ $row['details'] }}</span></td>
                                    <td><span class="bc-status bc-status-{{ str($row['result'])->slug() }}">{{ $row['result'] }}</span></td>
                                    <td class="text-end">
                                        <button class="bc-row-action" type="button" data-history-view="{{ $row['id'] }}"
                                                aria-label="{{ __('bloodcare.history.view_event', ['reference' => $row['reference']]) }}">
                                            <i class="la la-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            @foreach ($rows as $row)
                                <tr>
                                    @foreach ($row as $index => $cell)
                                        <td>
                                            @if ($index === 0)
                                                <strong>{{ $cell }}</strong>
                                            @elseif (in_array($cell, ['A+', 'A-', 'A−', 'B+', 'B-', 'B−', 'AB+', 'AB-', 'AB−', 'O+', 'O-', 'O−']))
                                                <span class="bc-group-badge">{{ $cell }}</span>
                                            @elseif ($index === array_key_last($row))
                                                <span class="bc-status bc-status-{{ str($cell)->slug() }}">
                                                    {{ __('bloodcare.dashboard.status.'.str($cell)->slug('_')) }}
                                                </span>
                                            @else
                                                {{ $cell }}
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="text-end">
                                        <button class="bc-row-action" type="button" disabled aria-label="{{ __('bloodcare.module.preview_actions') }}">
                                            <i class="la la-ellipsis-h"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            @if ($moduleKey === 'appointments')
                <div class="bc-appointment-calendar" id="bc-appointment-calendar-view" hidden>
                    <div class="bc-calendar-weekdays" id="bc-calendar-weekdays"></div>
                    <div class="bc-calendar-grid" id="bc-calendar-grid"></div>
                </div>
            @endif

            <div class="bc-table-footer" @if ($moduleKey === 'appointments') id="bc-appointment-table-footer" @endif>
                <span
                    @if ($moduleKey === 'inventory') id="bc-inventory-result-count"
                    @elseif ($moduleKey === 'donors') id="bc-donor-result-count"
                    @elseif ($moduleKey === 'cards') id="bc-card-result-count"
                    @elseif ($moduleKey === 'donations') id="bc-donation-result-count"
                    @elseif ($moduleKey === 'appointments') id="bc-appointment-result-count"
                    @elseif ($moduleKey === 'history') id="bc-history-result-count"
                    @endif>
                    @if ($moduleKey === 'inventory')
                        {{ __('bloodcare.inventory.showing_range', ['from' => 1, 'to' => min(5, count($inventoryData['records'])), 'total' => count($inventoryData['records'])]) }}
                    @elseif ($moduleKey === 'donors')
                        {{ __('bloodcare.donors.showing_range', ['from' => 1, 'to' => min(5, count($donorData['records'])), 'total' => count($donorData['records'])]) }}
                    @elseif ($moduleKey === 'cards')
                        {{ __('bloodcare.cards.showing_range', ['from' => 1, 'to' => min(5, count($cardData['records'])), 'total' => count($cardData['records'])]) }}
                    @elseif ($moduleKey === 'donations')
                        {{ __('bloodcare.donations.showing_range', ['from' => 1, 'to' => min(5, count($donationData['records'])), 'total' => count($donationData['records'])]) }}
                    @elseif ($moduleKey === 'appointments')
                        {{ __('bloodcare.appointments.showing_range', ['from' => 1, 'to' => min(5, count($appointmentData['records'])), 'total' => count($appointmentData['records'])]) }}
                    @elseif ($moduleKey === 'history')
                        {{ __('bloodcare.history.showing_range', ['from' => 1, 'to' => min(5, count($historyData['records'])), 'total' => count($historyData['records'])]) }}
                    @else
                        {{ __('bloodcare.module.showing', ['count' => count($rows)]) }}
                    @endif
                </span>
                <span class="bc-pagination-preview">
                    <button
                        @if ($moduleKey === 'inventory') id="bc-inventory-prev" type="button"
                        @elseif ($moduleKey === 'donors') id="bc-donor-prev" type="button"
                        @elseif ($moduleKey === 'cards') id="bc-card-prev" type="button"
                        @elseif ($moduleKey === 'donations') id="bc-donation-prev" type="button"
                        @elseif ($moduleKey === 'appointments') id="bc-appointment-prev" type="button"
                        @elseif ($moduleKey === 'history') id="bc-history-prev" type="button"
                        @else disabled
                        @endif
                        aria-label="{{ $moduleKey === 'history' ? __('bloodcare.history.previous_page') : ($moduleKey === 'appointments' ? __('bloodcare.appointments.previous_page') : ($moduleKey === 'donations' ? __('bloodcare.donations.previous_page') : ($moduleKey === 'cards' ? __('bloodcare.cards.previous_page') : ($moduleKey === 'donors' ? __('bloodcare.donors.previous_page') : __('bloodcare.inventory.previous_page'))))) }}">
                        <i class="la la-angle-left"></i>
                    </button>
                    <strong
                        @if ($moduleKey === 'inventory') id="bc-inventory-page"
                        @elseif ($moduleKey === 'donors') id="bc-donor-page"
                        @elseif ($moduleKey === 'cards') id="bc-card-page"
                        @elseif ($moduleKey === 'donations') id="bc-donation-page"
                        @elseif ($moduleKey === 'appointments') id="bc-appointment-page"
                        @elseif ($moduleKey === 'history') id="bc-history-page"
                        @endif>1</strong>
                    <button
                        @if ($moduleKey === 'inventory') id="bc-inventory-next" type="button"
                        @elseif ($moduleKey === 'donors') id="bc-donor-next" type="button"
                        @elseif ($moduleKey === 'cards') id="bc-card-next" type="button"
                        @elseif ($moduleKey === 'donations') id="bc-donation-next" type="button"
                        @elseif ($moduleKey === 'appointments') id="bc-appointment-next" type="button"
                        @elseif ($moduleKey === 'history') id="bc-history-next" type="button"
                        @else disabled
                        @endif
                        aria-label="{{ $moduleKey === 'history' ? __('bloodcare.history.next_page') : ($moduleKey === 'appointments' ? __('bloodcare.appointments.next_page') : ($moduleKey === 'donations' ? __('bloodcare.donations.next_page') : ($moduleKey === 'cards' ? __('bloodcare.cards.next_page') : ($moduleKey === 'donors' ? __('bloodcare.donors.next_page') : __('bloodcare.inventory.next_page'))))) }}">
                        <i class="la la-angle-right"></i>
                    </button>
                </span>
            </div>
        </section>

        <div class="bc-prototype-note">
            <i class="la la-info-circle"></i>
            <span>
                @if ($moduleKey === 'inventory')
                    <strong>{{ __('bloodcare.inventory.note_title') }}</strong>
                    {{ __('bloodcare.inventory.note_text') }}
                @elseif ($moduleKey === 'donors')
                    <strong>{{ __('bloodcare.donors.note_title') }}</strong>
                    {{ __('bloodcare.donors.note_text') }}
                @elseif ($moduleKey === 'cards')
                    <strong>{{ __('bloodcare.cards.note_title') }}</strong>
                    {{ __('bloodcare.cards.note_text') }}
                @elseif ($moduleKey === 'donations')
                    <strong>{{ __('bloodcare.donations.note_title') }}</strong>
                    {{ __('bloodcare.donations.note_text') }}
                @elseif ($moduleKey === 'appointments')
                    <strong>{{ __('bloodcare.appointments.note_title') }}</strong>
                    {{ __('bloodcare.appointments.note_text') }}
                @elseif ($moduleKey === 'history')
                    <strong>{{ __('bloodcare.history.note_title') }}</strong>
                    {{ __('bloodcare.history.note_text') }}
                @else
                    <strong>{{ __('bloodcare.module.note_title') }}</strong>
                    {{ __('bloodcare.module.note_text') }}
                @endif
            </span>
        </div>

        @if ($moduleKey === 'inventory')
            @include('admin.partials.inventory-interactions')
        @elseif ($moduleKey === 'donors')
            @include('admin.partials.donor-interactions')
        @elseif ($moduleKey === 'cards')
            @include('admin.partials.card-interactions')
        @elseif ($moduleKey === 'donations')
            @include('admin.partials.donation-interactions')
        @elseif ($moduleKey === 'appointments')
            @include('admin.partials.appointment-interactions')
        @elseif ($moduleKey === 'history')
            @include('admin.partials.history-interactions')
        @endif
    </div>
@endsection
