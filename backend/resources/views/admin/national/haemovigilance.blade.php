@extends(backpack_view('blank'))
@section('title', __('bloodcare.national.haemovigilance.title'))
@push('before_styles') @include('admin.partials.favicon') @endpush
@push('after_styles') <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}?v={{ filemtime(public_path('css/bloodcare-admin.css')) }}"> @endpush
@push('after_scripts') <script src="{{ asset('js/bloodcare-national-filters.js') }}?v={{ filemtime(public_path('js/bloodcare-national-filters.js')) }}" defer></script> @endpush

@section('content')
@php
    $statusOptions = ['' => __('bloodcare.national.haemovigilance.all_statuses')];
    foreach (\App\Models\AdverseReaction::STATUSES as $value) $statusOptions[$value] = __('bloodcare.national.haemovigilance.statuses.'.$value);
    $severityOptions = ['' => __('bloodcare.national.haemovigilance.all_severities')];
    foreach (['mild','moderate','severe','life_threatening'] as $value) $severityOptions[$value] = __('bloodcare.national.portal.'.$value);
@endphp
<div class="bc-admin-page bc-module-page bc-national-page bc-haemovigilance-page">
    <header class="bc-page-heading">
        <div class="bc-module-title"><span class="bc-module-icon"><i class="la la-heartbeat"></i></span><div>
            <p class="bc-eyebrow">{{ __('bloodcare.national.haemovigilance.eyebrow') }}</p>
            <h1>{{ __('bloodcare.national.haemovigilance.title') }}</h1>
            <p>{{ __('bloodcare.national.haemovigilance.description') }}</p>
        </div></div>
        @include('admin.partials.utility-controls')
    </header>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <section class="bc-module-metrics bc-haemo-metrics" aria-label="{{ __('bloodcare.national.haemovigilance.summary') }}">
        @foreach([
            [__('bloodcare.national.haemovigilance.total_reports'), $metrics['total'], 'blue'],
            [__('bloodcare.national.haemovigilance.open_cases'), $metrics['open'], 'amber'],
            [__('bloodcare.national.haemovigilance.serious_open'), $metrics['serious'], 'red'],
            [__('bloodcare.national.haemovigilance.closed_cases'), $metrics['closed'], 'green'],
        ] as [$label, $value, $tone])
            <article class="bc-mini-metric"><span class="bc-mini-dot bc-dot-{{ $tone }}"></span><div><small>{{ $label }}</small><strong>{{ $value }}</strong></div></article>
        @endforeach
    </section>

    <section class="bc-panel bc-haemo-queue-panel">
        <div class="bc-panel-heading bc-haemo-queue-heading">
            <div><h2>{{ __('bloodcare.national.haemovigilance.queue_title') }}</h2><p>{{ __('bloodcare.national.haemovigilance.queue_help') }}</p></div>
        </div>

        <form class="bc-haemo-filter" method="GET" action="{{ route('bloodcare.admin.haemovigilance') }}">
            <label class="bc-haemo-search" for="bc-haemo-search"><span>{{ __('bloodcare.national.haemovigilance.search') }}</span><div><i class="la la-search" aria-hidden="true"></i><input id="bc-haemo-search" name="q" type="search" value="{{ $search }}" maxlength="120" placeholder="{{ __('bloodcare.national.haemovigilance.search_placeholder') }}" autocomplete="off"></div></label>
            @include('admin.national.partials.haemovigilance-select', ['id'=>'bc-haemo-status-filter','name'=>'status','label'=>__('bloodcare.national.common.status'),'selected'=>$status ?? '','options'=>$statusOptions,'icon'=>'la-tasks'])
            @include('admin.national.partials.haemovigilance-select', ['id'=>'bc-haemo-severity-filter','name'=>'severity','label'=>__('bloodcare.national.haemovigilance.severity'),'selected'=>$severity ?? '','options'=>$severityOptions,'icon'=>'la-exclamation-triangle'])
            <button class="btn bc-btn-primary" type="submit"><i class="la la-search"></i> {{ __('bloodcare.national.common.search') }}</button>
            @if($search !== '' || $status || $severity)<a class="btn bc-btn-outline" href="{{ route('bloodcare.admin.haemovigilance') }}">{{ __('bloodcare.national.common.clear') }}</a>@endif
        </form>

        <div class="table-responsive">
            <table class="table table-vcenter bc-module-table bc-haemo-queue-table">
                <thead><tr>
                    <th>{{ __('bloodcare.national.haemovigilance.case') }}</th>
                    <th>{{ __('bloodcare.national.hospitals.hospital') }}</th>
                    <th>{{ __('bloodcare.inventory.unit_number') }}</th>
                    <th>{{ __('bloodcare.national.haemovigilance.occurred_at') }}</th>
                    <th>{{ __('bloodcare.national.haemovigilance.severity') }}</th>
                    <th>{{ __('bloodcare.national.common.status') }}</th>
                    <th class="text-end">{{ __('bloodcare.national.haemovigilance.actions') }}</th>
                </tr></thead>
                <tbody>
                @forelse($reports as $report)
                    @php $unit=$report->allocation?->unit; $bloodRequest=$report->allocation?->request; @endphp
                    <tr class="{{ in_array($report->severity,['severe','life_threatening'],true) && $report->status !== 'closed' ? 'bc-haemo-serious-row' : '' }}">
                        <td><strong>{{ $report->reference }}</strong><small>{{ $bloodRequest?->reference ?: __('bloodcare.national.common.not_provided') }}</small></td>
                        <td><strong>{{ $report->hospital?->name ?: __('bloodcare.national.common.not_provided') }}</strong></td>
                        <td>{{ $unit?->unit_number ?: __('bloodcare.national.common.not_provided') }}</td>
                        <td>{{ $report->occurred_at?->format('Y-m-d H:i') ?: '—' }}</td>
                        <td><span class="bc-status bc-haemo-severity-{{ $report->severity }}">{{ __('bloodcare.national.portal.'.$report->severity) }}</span></td>
                        <td><span class="bc-status bc-haemo-status-{{ $report->status }}">{{ __('bloodcare.national.haemovigilance.statuses.'.$report->status) }}</span></td>
                        <td class="text-end"><a class="bc-haemo-view-button" href="{{ route('bloodcare.admin.haemovigilance.show',$report) }}" aria-label="{{ __('bloodcare.national.haemovigilance.view_case_reference',['reference'=>$report->reference]) }}"><i class="la la-arrow-right" aria-hidden="true"></i><span>{{ __('bloodcare.national.haemovigilance.view_case') }}</span></a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="bc-haemo-empty"><i class="la la-check-circle"></i><h2>{{ __('bloodcare.national.haemovigilance.empty_title') }}</h2><p>{{ __('bloodcare.national.haemovigilance.empty_text') }}</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($reports->total() > 0)
            <footer class="bc-lab-pagination">
                <span>{{ __('bloodcare.national.common.showing', ['from'=>$reports->firstItem(), 'to'=>$reports->lastItem(), 'total'=>$reports->total()]) }}</span>
                <nav aria-label="{{ __('bloodcare.national.haemovigilance.pages') }}">
                    @if($reports->onFirstPage())<span class="bc-lab-page-arrow is-disabled"><i class="la la-angle-left"></i></span>@else<a class="bc-lab-page-arrow" href="{{ $reports->previousPageUrl() }}"><i class="la la-angle-left"></i></a>@endif
                    @foreach($reports->getUrlRange(max(1,$reports->currentPage()-2),min($reports->lastPage(),$reports->currentPage()+2)) as $page=>$url)<a href="{{ $url }}" class="{{ $page === $reports->currentPage() ? 'is-active' : '' }}" @if($page === $reports->currentPage()) aria-current="page" @endif>{{ $page }}</a>@endforeach
                    @if($reports->hasMorePages())<a class="bc-lab-page-arrow" href="{{ $reports->nextPageUrl() }}"><i class="la la-angle-right"></i></a>@else<span class="bc-lab-page-arrow is-disabled"><i class="la la-angle-right"></i></span>@endif
                </nav>
            </footer>
        @endif
    </section>
</div>
@endsection
