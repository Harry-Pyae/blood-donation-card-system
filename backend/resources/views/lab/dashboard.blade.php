@extends(backpack_view('blank'))

@section('title', __('bloodcare.lab_workspace.dashboard.title'))
@push('before_styles') @include('admin.partials.favicon') @endpush
@push('after_styles') <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}?v={{ filemtime(public_path('css/bloodcare-admin.css')) }}"> @endpush

@section('content')
<div class="bc-admin-page bc-module-page bc-lab-workspace-page">
    <header class="bc-page-heading">
        <div class="bc-module-title">
            <span class="bc-module-icon"><i class="la la-flask"></i></span>
            <div>
                <p class="bc-eyebrow">{{ __('bloodcare.lab_workspace.dashboard.eyebrow') }}</p>
                <h1>{{ __('bloodcare.lab_workspace.dashboard.title') }}</h1>
                <p>{{ __('bloodcare.lab_workspace.dashboard.description') }}</p>
            </div>
        </div>
        @include('admin.partials.utility-controls')
    </header>

    <section class="bc-module-metrics bc-lab-workspace-metrics" aria-label="{{ __('bloodcare.lab_workspace.dashboard.summary') }}">
        <article class="bc-mini-metric"><span class="bc-mini-dot bc-dot-amber"></span><div><small>{{ __('bloodcare.lab_workspace.dashboard.pending_tests') }}</small><strong>{{ number_format($pendingTests) }}</strong></div></article>
        <article class="bc-mini-metric"><span class="bc-mini-dot bc-dot-green"></span><div><small>{{ __('bloodcare.lab_workspace.dashboard.released_today') }}</small><strong>{{ number_format($releasedToday) }}</strong></div></article>
        <article class="bc-mini-metric"><span class="bc-mini-dot bc-dot-blue"></span><div><small>{{ __('bloodcare.lab_workspace.dashboard.component_stock') }}</small><strong>{{ number_format($componentStock) }}</strong></div></article>
        <article class="bc-mini-metric"><span class="bc-mini-dot bc-dot-red"></span><div><small>{{ __('bloodcare.lab_workspace.dashboard.expiring_soon') }}</small><strong>{{ number_format($expiringSoon) }}</strong></div></article>
    </section>

    <section class="bc-lab-quick-grid" aria-label="{{ __('bloodcare.lab_workspace.dashboard.quick_actions') }}">
        <a class="bc-lab-quick-card" href="{{ route('bloodcare.lab.laboratory') }}"><i class="la la-vials"></i><span><strong>{{ __('bloodcare.menu.laboratory') }}</strong><small>{{ __('bloodcare.lab_workspace.dashboard.laboratory_help') }}</small></span><i class="la la-arrow-right"></i></a>
        <a class="bc-lab-quick-card" href="{{ route('bloodcare.lab.components') }}"><i class="la la-layer-group"></i><span><strong>{{ __('bloodcare.menu.components') }}</strong><small>{{ __('bloodcare.lab_workspace.dashboard.components_help') }}</small></span><i class="la la-arrow-right"></i></a>
        <a class="bc-lab-quick-card" href="{{ route('bloodcare.lab.inventory') }}"><i class="la la-tint"></i><span><strong>{{ __('bloodcare.menu.inventory') }}</strong><small>{{ __('bloodcare.lab_workspace.dashboard.inventory_help') }}</small></span><i class="la la-arrow-right"></i></a>
    </section>

    <section class="bc-panel bc-module-table-panel bc-lab-workspace-recent">
        <div class="bc-panel-heading"><div><h2>{{ __('bloodcare.lab_workspace.dashboard.recent_title') }}</h2><p>{{ __('bloodcare.lab_workspace.dashboard.recent_help') }}</p></div><a class="btn bc-btn-outline" href="{{ route('bloodcare.lab.history') }}">{{ __('bloodcare.lab_workspace.dashboard.view_history') }}</a></div>
        <div class="table-responsive">
            <table class="table table-vcenter bc-module-table">
                <thead><tr><th>{{ __('bloodcare.lab_workspace.history.reference') }}</th><th>{{ __('bloodcare.national.laboratory.donation_unit') }}</th><th>{{ __('bloodcare.national.common.blood_group') }}</th><th>{{ __('bloodcare.national.common.status') }}</th><th>{{ __('bloodcare.national.laboratory.tested_by') }}</th><th>{{ __('bloodcare.national.laboratory.tested_at') }}</th></tr></thead>
                <tbody>
                    @forelse($recentTests as $test)
                        <tr>
                            <td><strong>{{ $test->reference }}</strong></td>
                            <td>{{ $test->donation?->reference ?? '—' }}</td>
                            <td><span class="bc-group-badge">{{ $test->confirmed_blood_group }}</span></td>
                            <td><span class="bc-status bc-status-{{ $test->release_status }}">{{ __('bloodcare.national.laboratory.'.$test->release_status) }}</span></td>
                            <td>{{ $test->testedBy?->name ?? '—' }}</td>
                            <td>{{ $test->tested_at?->format('d M Y, H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-5">{{ __('bloodcare.lab_workspace.dashboard.no_recent') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
