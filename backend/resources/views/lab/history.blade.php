@extends(backpack_view('blank'))

@section('title', __('bloodcare.lab_workspace.history.title'))
@push('before_styles') @include('admin.partials.favicon') @endpush
@push('after_styles') <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}?v={{ filemtime(public_path('css/bloodcare-admin.css')) }}"> @endpush
@push('after_scripts') <script src="{{ asset('js/bloodcare-national-filters.js') }}?v={{ filemtime(public_path('js/bloodcare-national-filters.js')) }}" defer></script> @endpush

@section('content')
<div class="bc-admin-page bc-module-page bc-lab-workspace-page">
    <header class="bc-page-heading">
        <div class="bc-module-title"><span class="bc-module-icon"><i class="la la-history"></i></span><div>
            <p class="bc-eyebrow">{{ __('bloodcare.lab_workspace.history.eyebrow') }}</p>
            <h1>{{ __('bloodcare.lab_workspace.history.title') }}</h1>
            <p>{{ __('bloodcare.lab_workspace.history.description') }}</p>
        </div></div>
        @include('admin.partials.utility-controls')
    </header>

    <section class="bc-panel bc-module-table-panel bc-lab-history-panel">
        <form class="bc-lab-filter-bar" method="GET" action="{{ route('bloodcare.lab.history') }}">
            <label class="bc-lab-search" for="bc-lab-history-search"><i class="la la-search" aria-hidden="true"></i><input id="bc-lab-history-search" name="q" type="search" value="{{ $search }}" placeholder="{{ __('bloodcare.lab_workspace.history.search_placeholder') }}" autocomplete="off"></label>
            <div class="bc-filter-dropdown bc-lab-filter-dropdown" data-bc-national-select>
                <i class="la la-filter bc-filter-dropdown-icon" aria-hidden="true"></i>
                <select class="visually-hidden" id="bc-lab-history-type" name="type" tabindex="-1" aria-hidden="true">
                    @foreach(['all','laboratory','components','inventory'] as $value)<option value="{{ $value }}" @selected($type === $value)>{{ __('bloodcare.lab_workspace.history.type_'.$value) }}</option>@endforeach
                </select>
                <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="bc-lab-history-type-menu"><span data-bc-select-label>{{ __('bloodcare.lab_workspace.history.type_'.$type) }}</span><i class="la la-angle-down" aria-hidden="true"></i></button>
                <div class="bc-filter-dropdown-menu" id="bc-lab-history-type-menu" role="listbox" aria-label="{{ __('bloodcare.lab_workspace.history.type_label') }}" hidden>
                    @foreach(['all','laboratory','components','inventory'] as $value)<button type="button" role="option" data-value="{{ $value }}" aria-selected="{{ $type === $value ? 'true' : 'false' }}"><span>{{ __('bloodcare.lab_workspace.history.type_'.$value) }}</span><i class="la la-check" aria-hidden="true"></i></button>@endforeach
                </div>
            </div>
            <button class="btn bc-btn-primary bc-lab-filter-submit" type="submit"><i class="la la-filter"></i> {{ __('bloodcare.national.common.apply') }}</button>
            @if($search !== '' || $type !== 'all')<a class="btn bc-btn-outline bc-lab-filter-reset" href="{{ route('bloodcare.lab.history') }}">{{ __('bloodcare.national.common.clear') }}</a>@endif
        </form>

        <div class="bc-lab-privacy-line"><i class="la la-user-shield"></i><span>{{ __('bloodcare.lab_workspace.history.privacy') }}</span></div>

        <div class="table-responsive"><table class="table table-vcenter bc-module-table">
            <thead><tr><th>{{ __('bloodcare.lab_workspace.history.date') }}</th><th>{{ __('bloodcare.lab_workspace.history.type') }}</th><th>{{ __('bloodcare.lab_workspace.history.action') }}</th><th>{{ __('bloodcare.lab_workspace.history.reference') }}</th><th>{{ __('bloodcare.lab_workspace.history.staff') }}</th><th>{{ __('bloodcare.lab_workspace.history.result') }}</th></tr></thead>
            <tbody>
                @forelse($logs as $log)
                    @php
                        $reference = $log->subject?->reference ?? $log->subject?->unit_number ?? $log->reference;
                        $typeKey = match($log->source) { 'admin-laboratory' => 'laboratory', 'admin-components' => 'components', default => 'inventory' };
                    @endphp
                    <tr>
                        <td>{{ $log->created_at?->format('d M Y, H:i') ?? '—' }}</td>
                        <td><span class="bc-lab-history-type bc-lab-history-type-{{ $typeKey }}">{{ __('bloodcare.lab_workspace.history.type_'.$typeKey) }}</span></td>
                        <td><strong>{{ $log->action }}</strong><br><small>{{ $log->details }}</small></td>
                        <td>{{ $reference }}</td>
                        <td>{{ $log->user?->name ?? '—' }}</td>
                        <td>{{ $log->result ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5">{{ __('bloodcare.lab_workspace.history.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if($logs->total() > 0)
            <footer class="bc-lab-pagination">
                <span>{{ __('bloodcare.national.common.showing', ['from' => $logs->firstItem(), 'to' => $logs->lastItem(), 'total' => $logs->total()]) }}</span>
                <nav aria-label="{{ __('bloodcare.lab_workspace.history.pages') }}">
                    @if($logs->onFirstPage())<span class="bc-lab-page-arrow is-disabled" aria-hidden="true"><i class="la la-angle-left"></i></span>@else<a class="bc-lab-page-arrow" href="{{ $logs->previousPageUrl() }}" aria-label="{{ __('bloodcare.national.common.previous') }}"><i class="la la-angle-left"></i></a>@endif
                    @foreach($logs->getUrlRange(max(1, $logs->currentPage() - 2), min($logs->lastPage(), $logs->currentPage() + 2)) as $page => $url)<a href="{{ $url }}" class="{{ $page === $logs->currentPage() ? 'is-active' : '' }}" @if($page === $logs->currentPage()) aria-current="page" @endif>{{ $page }}</a>@endforeach
                    @if($logs->hasMorePages())<a class="bc-lab-page-arrow" href="{{ $logs->nextPageUrl() }}" aria-label="{{ __('bloodcare.national.common.next') }}"><i class="la la-angle-right"></i></a>@else<span class="bc-lab-page-arrow is-disabled" aria-hidden="true"><i class="la la-angle-right"></i></span>@endif
                </nav>
            </footer>
        @endif
    </section>
</div>
@endsection
