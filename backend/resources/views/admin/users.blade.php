@extends(backpack_view('blank'))

@section('title', $title)

@push('before_styles')
    @include('admin.partials.favicon')
@endpush

@push('after_styles')
    <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}?v={{ filemtime(public_path('css/bloodcare-admin.css')) }}">
@endpush

@push('after_scripts')
    <script src="{{ asset('js/bloodcare-users.js') }}?v={{ filemtime(public_path('js/bloodcare-users.js')) }}" defer></script>
@endpush

@section('content')
    <div class="bc-admin-page bc-module-page" data-bc-module="users">
        <header class="bc-page-heading">
            <div class="bc-module-title">
                <span class="bc-module-icon"><i class="la la-user-shield"></i></span>
                <div>
                    <p class="bc-eyebrow">{{ $eyebrow }}</p>
                    <h1>{{ $title }}</h1>
                    <p>{{ $description }}</p>
                </div>
            </div>
            <div class="bc-heading-tools">
                @include('admin.partials.utility-controls')
                <div class="bc-heading-actions">
                    <button class="bc-prototype-badge" id="bc-data-source-button" type="button">
                        <i class="la la-database"></i> {{ __('bloodcare.module.database_live') }}
                    </button>
                </div>
            </div>
        </header>

        @php
            $totalAccounts = count($userData['records']);
            $privilegedAccounts = collect($userData['records'])
                ->whereIn('role', ['System Staff', 'System Admin', 'Lab Staff', 'Lab Admin'])
                ->where('status', 'Active')
                ->count();
            $pendingAccounts = collect($userData['records'])->where('status', 'Pending')->count();
            $bannedAccounts = collect($userData['records'])->where('status', 'Banned')->count();
        @endphp
        <section class="bc-module-metrics">
            @foreach ([
                ['key' => 'total', 'label' => __('bloodcare.modules.users.metrics.0'), 'value' => $totalAccounts, 'tone' => 'blue'],
                ['key' => 'privileged', 'label' => __('bloodcare.modules.users.metrics.1'), 'value' => $privilegedAccounts, 'tone' => 'green'],
                ['key' => 'pending', 'label' => __('bloodcare.modules.users.metrics.2'), 'value' => $pendingAccounts, 'tone' => 'amber'],
                ['key' => 'banned', 'label' => __('bloodcare.modules.users.metrics.3'), 'value' => $bannedAccounts, 'tone' => 'red'],
            ] as $metric)
                <article class="bc-mini-metric" data-user-metric="{{ $metric['key'] }}">
                    <span class="bc-mini-dot bc-dot-{{ $metric['tone'] }}"></span>
                    <div><small>{{ $metric['label'] }}</small><strong>{{ $metric['value'] }}</strong></div>
                </article>
            @endforeach
        </section>

        <section class="bc-panel bc-module-table-panel">
            <div class="bc-filter-bar" aria-label="{{ __('bloodcare.users.filters') }}">
                <label class="bc-filter-search" for="bc-user-search">
                    <i class="la la-search"></i>
                    <input id="bc-user-search" type="search" placeholder="{{ $filters[0] }}" autocomplete="off">
                </label>

                @php
                    $accountFilters = [
                        [
                            'id' => 'bc-user-role',
                            'icon' => 'la-user-tag',
                            'label' => __('bloodcare.users.all_roles'),
                            'options' => [
                                ['value' => 'User', 'label' => __('bloodcare.users.user_role')],
                                ['value' => 'System Staff', 'label' => __('bloodcare.users.system_staff_role')],
                                ['value' => 'System Admin', 'label' => __('bloodcare.users.system_admin_role')],
                                ['value' => 'Lab Staff', 'label' => __('bloodcare.users.lab_staff_role')],
                                ['value' => 'Lab Admin', 'label' => __('bloodcare.users.lab_admin_role')],
                            ],
                        ],
                        [
                            'id' => 'bc-user-status',
                            'icon' => 'la-shield-alt',
                            'label' => __('bloodcare.users.all_statuses'),
                            'options' => [
                                ['value' => 'Active', 'label' => __('bloodcare.users.active')],
                                ['value' => 'Pending', 'label' => __('bloodcare.users.pending')],
                                ['value' => 'Rejected', 'label' => __('bloodcare.users.rejected')],
                                ['value' => 'Banned', 'label' => __('bloodcare.users.banned')],
                            ],
                        ],
                    ];
                @endphp

                @foreach ($accountFilters as $filter)
                    <div class="bc-filter-dropdown" data-bc-user-select>
                        <i class="la {{ $filter['icon'] }} bc-filter-dropdown-icon" aria-hidden="true"></i>
                        <select class="visually-hidden" id="{{ $filter['id'] }}" tabindex="-1" aria-hidden="true">
                            <option value="all">{{ $filter['label'] }}</option>
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
                            <button type="button" role="option" data-value="all" aria-selected="true">
                                <span>{{ $filter['label'] }}</span><i class="la la-check" aria-hidden="true"></i>
                            </button>
                            @foreach ($filter['options'] as $option)
                                <button type="button" role="option" data-value="{{ $option['value'] }}" aria-selected="false">
                                    <span>{{ $option['label'] }}</span><i class="la la-check" aria-hidden="true"></i>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter bc-module-table">
                    <thead>
                        <tr>
                            @foreach ($columns as $column)
                                <th>{{ $column }}</th>
                            @endforeach
                            <th class="text-end">{{ __('bloodcare.module.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="bc-user-table-body">
                        @foreach (array_slice($userData['records'], 0, 5) as $row)
                            <tr>
                                <td>
                                    <span class="bc-donor-cell">
                                        <span class="bc-donor-table-avatar">{{ mb_strtoupper(mb_substr($row['name'], 0, 1, 'UTF-8'), 'UTF-8') }}</span>
                                        <span class="bc-user-name-cell">
                                            <strong>{{ $row['name'] }}</strong>
                                            @if ($row['current'])
                                                <small>{{ __('bloodcare.users.current_session') }}</small>
                                            @endif
                                        </span>
                                    </span>
                                </td>
                                <td>{{ $row['email'] }}</td>
                                @php
                                    $roleKey = match ($row['role']) {
                                        'System Staff' => 'system_staff_role',
                                        'System Admin' => 'system_admin_role',
                                        'Lab Staff' => 'lab_staff_role',
                                        'Lab Admin' => 'lab_admin_role',
                                        default => 'user_role',
                                    };
                                @endphp
                                <td><span class="bc-user-role bc-user-role-{{ str($row['role'])->slug() }}">{{ __('bloodcare.users.'.$roleKey) }}</span></td>
                                <td><span class="bc-status bc-status-{{ strtolower($row['status']) }}">{{ __('bloodcare.users.'.strtolower($row['status'])) }}</span></td>
                                <td>{{ $row['lastLogin'] ? \Carbon\Carbon::parse($row['lastLogin'])->format('d M Y, H:i') : __('bloodcare.users.never') }}</td>
                                <td>{{ \Carbon\Carbon::parse($row['joinedAt'])->format('d M Y') }}</td>
                                <td class="text-end">
                                    <button class="bc-row-action" type="button" data-user-menu="{{ $row['id'] }}"
                                            aria-label="{{ __('bloodcare.users.open_actions', ['user' => $row['name']]) }}">
                                        <i class="la la-ellipsis-h"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="bc-table-footer">
                <span id="bc-user-result-count">
                    {{ __('bloodcare.users.showing_range', ['from' => 1, 'to' => min(5, count($userData['records'])), 'total' => count($userData['records'])]) }}
                </span>
                <span class="bc-pagination-preview">
                    <button id="bc-user-prev" type="button" aria-label="{{ __('bloodcare.users.previous_page') }}">
                        <i class="la la-angle-left"></i>
                    </button>
                    <strong id="bc-user-page">1</strong>
                    <button id="bc-user-next" type="button" aria-label="{{ __('bloodcare.users.next_page') }}">
                        <i class="la la-angle-right"></i>
                    </button>
                </span>
            </div>
        </section>

        <div class="bc-prototype-note">
            <i class="la la-info-circle"></i>
            <span>
                <strong>{{ __('bloodcare.users.note_title') }}</strong>
                {{ __('bloodcare.users.note_text') }}
            </span>
        </div>

        @include('admin.partials.user-interactions')
    </div>
@endsection
