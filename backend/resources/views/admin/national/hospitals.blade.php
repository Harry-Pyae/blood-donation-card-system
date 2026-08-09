@extends(backpack_view('blank'))
@section('title', __('bloodcare.national.hospitals.title'))
@push('before_styles') @include('admin.partials.favicon') @endpush
@push('after_styles') <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}?v={{ filemtime(public_path('css/bloodcare-admin.css')) }}"> @endpush
@push('after_scripts')
<script src="{{ asset('js/bloodcare-national-filters.js') }}?v={{ filemtime(public_path('js/bloodcare-national-filters.js')) }}" defer></script>
<script src="{{ asset('js/bloodcare-hospitals.js') }}?v={{ filemtime(public_path('js/bloodcare-hospitals.js')) }}" defer></script>
@endpush
@section('content')
@php
    $regionLabels = [
        'Ayeyarwady Region' => __('bloodcare.national.hospitals.regions.ayeyarwady'),
        'Bago Region' => __('bloodcare.national.hospitals.regions.bago'),
        'Magway Region' => __('bloodcare.national.hospitals.regions.magway'),
        'Mandalay Region' => __('bloodcare.national.hospitals.regions.mandalay'),
        'Sagaing Region' => __('bloodcare.national.hospitals.regions.sagaing'),
        'Tanintharyi Region' => __('bloodcare.national.hospitals.regions.tanintharyi'),
        'Yangon Region' => __('bloodcare.national.hospitals.regions.yangon'),
    ];
    $selectedRegion = (string) old('region', '');
@endphp
<div class="bc-admin-page bc-module-page bc-national-page">
    <header class="bc-page-heading">
        <div class="bc-module-title"><span class="bc-module-icon"><i class="la la-hospital"></i></span><div>
            <p class="bc-eyebrow">{{ __('bloodcare.national.hospitals.eyebrow') }}</p>
            <h1>{{ __('bloodcare.national.hospitals.title') }}</h1>
            <p>{{ __('bloodcare.national.hospitals.description') }}</p>
        </div></div>
        @include('admin.partials.utility-controls')
    </header>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="bc-national-grid">
        <section class="bc-panel bc-national-form-panel">
            <div class="bc-panel-heading"><h2>{{ __('bloodcare.national.hospitals.add') }}</h2></div>
            <form method="POST" action="{{ route('bloodcare.admin.hospitals.store') }}" class="bc-national-form">
                @csrf
                <p class="bc-hospital-code-note"><i class="la la-barcode" aria-hidden="true"></i><span>{{ __('bloodcare.national.hospitals.auto_code') }}</span></p>
                <label>{{ __('bloodcare.national.hospitals.name') }}<input name="name" value="{{ old('name') }}" required maxlength="180" placeholder="{{ __('bloodcare.national.hospitals.name_placeholder') }}" autocomplete="organization"></label>
                <div class="bc-hospital-region-field">
                    <label id="bc-hospital-region-label" for="bc-hospital-region">{{ __('bloodcare.national.hospitals.region') }}</label>
                    <div class="bc-filter-dropdown bc-lab-filter-dropdown bc-hospital-region-dropdown" data-bc-national-select>
                        <i class="la la-map-marker bc-filter-dropdown-icon" aria-hidden="true"></i>
                        <select class="visually-hidden" id="bc-hospital-region" name="region" tabindex="-1" aria-labelledby="bc-hospital-region-label">
                            <option value="" @selected($selectedRegion === '')>{{ __('bloodcare.national.hospitals.select_region') }}</option>
                            @foreach($regionLabels as $region => $label)<option value="{{ $region }}" @selected($selectedRegion === $region)>{{ $label }}</option>@endforeach
                        </select>
                        <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="bc-hospital-region-menu" aria-required="true">
                            <span data-bc-select-label>{{ $regionLabels[$selectedRegion] ?? __('bloodcare.national.hospitals.select_region') }}</span>
                            <i class="la la-angle-down" aria-hidden="true"></i>
                        </button>
                        <div class="bc-filter-dropdown-menu" id="bc-hospital-region-menu" role="listbox" aria-labelledby="bc-hospital-region-label" hidden>
                            <button type="button" role="option" data-value="" aria-selected="{{ $selectedRegion === '' ? 'true' : 'false' }}"><span>{{ __('bloodcare.national.hospitals.select_region') }}</span><i class="la la-check" aria-hidden="true"></i></button>
                            @foreach($regionLabels as $region => $label)
                                <button type="button" role="option" data-value="{{ $region }}" aria-selected="{{ $selectedRegion === $region ? 'true' : 'false' }}"><span>{{ $label }}</span><i class="la la-check" aria-hidden="true"></i></button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <label>{{ __('bloodcare.national.hospitals.address') }}<input name="address" value="{{ old('address') }}" maxlength="255" placeholder="{{ __('bloodcare.national.hospitals.address_placeholder') }}" autocomplete="street-address"></label>
                <label>{{ __('bloodcare.national.hospitals.phone') }}<input name="phone" value="{{ old('phone') }}" maxlength="30" placeholder="{{ __('bloodcare.national.hospitals.phone_placeholder') }}" autocomplete="tel"></label>
                <hr>
                <label>{{ __('bloodcare.national.hospitals.contact') }}<input name="contact_name" value="{{ old('contact_name') }}" required maxlength="150" placeholder="{{ __('bloodcare.national.hospitals.contact_placeholder') }}" autocomplete="name"></label>
                <label>{{ __('bloodcare.national.hospitals.email') }}<input name="email" type="email" value="{{ old('email') }}" required placeholder="{{ __('bloodcare.national.hospitals.email_placeholder') }}" autocomplete="email"></label>
                <label>{{ __('bloodcare.national.hospitals.password') }}<input name="password" type="password" required minlength="8" placeholder="{{ __('bloodcare.national.hospitals.password_placeholder') }}" autocomplete="new-password"></label>
                <button class="btn bc-btn-primary"><i class="la la-plus-circle" aria-hidden="true"></i> {{ __('bloodcare.national.hospitals.create') }}</button>
            </form>
        </section>

        <section class="bc-panel bc-module-table-panel">
            <form class="bc-lab-filter-bar bc-hospital-search-bar" method="GET" action="{{ route('bloodcare.admin.hospitals') }}">
                <label class="bc-lab-search" for="bc-hospital-search">
                    <i class="la la-search" aria-hidden="true"></i>
                    <input id="bc-hospital-search" name="q" type="search" value="{{ $search }}" placeholder="{{ __('bloodcare.national.hospitals.search_placeholder') }}" autocomplete="off">
                </label>
                <button class="btn bc-btn-primary bc-lab-filter-submit" type="submit"><i class="la la-search" aria-hidden="true"></i> {{ __('bloodcare.national.common.search') }}</button>
                @if($search !== '')<a class="btn bc-btn-outline bc-lab-filter-reset" href="{{ route('bloodcare.admin.hospitals') }}">{{ __('bloodcare.national.common.clear') }}</a>@endif
            </form>

            <div class="table-responsive"><table class="table table-vcenter bc-module-table bc-hospital-table">
                <thead><tr><th>{{ __('bloodcare.national.hospitals.hospital') }}</th><th>{{ __('bloodcare.national.hospitals.region') }}</th><th>{{ __('bloodcare.national.common.status') }}</th><th>{{ __('bloodcare.national.hospitals.actions') }}</th></tr></thead>
                <tbody>
                @forelse($hospitals as $hospital)
                    <tr>
                        <td><strong>{{ $hospital->name }}</strong></td>
                        <td>{{ $regionLabels[$hospital->region] ?? ($hospital->region ?: '—') }}</td>
                        <td><span class="bc-status bc-status-{{ $hospital->is_active ? 'active' : 'inactive' }}">{{ $hospital->is_active ? __('bloodcare.national.common.active') : __('bloodcare.national.common.inactive') }}</span></td>
                        <td><div class="bc-hospital-row-actions">
                            <button class="bc-hospital-action-button" type="button" data-bc-hospital-open="details-{{ $hospital->id }}" title="{{ __('bloodcare.national.hospitals.view_details') }}" aria-label="{{ __('bloodcare.national.hospitals.view_details') }}"><i class="la la-eye" aria-hidden="true"></i></button>
                            <button class="bc-hospital-action-button" type="button" data-bc-hospital-open="edit-{{ $hospital->id }}" title="{{ __('bloodcare.national.hospitals.edit') }}" aria-label="{{ __('bloodcare.national.hospitals.edit') }}"><i class="la la-pen" aria-hidden="true"></i></button>
                            <button class="bc-hospital-action-button bc-hospital-action-danger" type="button" data-bc-hospital-open="delete-{{ $hospital->id }}" title="{{ __('bloodcare.national.hospitals.delete') }}" aria-label="{{ __('bloodcare.national.hospitals.delete') }}"><i class="la la-trash" aria-hidden="true"></i></button>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-5">{{ __('bloodcare.national.hospitals.none') }}</td></tr>
                @endforelse
                </tbody>
            </table></div>

            @if($hospitals->total() > 0)
                <footer class="bc-lab-pagination">
                    <span>{{ __('bloodcare.national.common.showing', ['from' => $hospitals->firstItem(), 'to' => $hospitals->lastItem(), 'total' => $hospitals->total()]) }}</span>
                    <nav aria-label="{{ __('bloodcare.national.hospitals.pages') }}">
                        @if($hospitals->onFirstPage())<span class="bc-lab-page-arrow is-disabled" aria-hidden="true"><i class="la la-angle-left"></i></span>@else<a class="bc-lab-page-arrow" href="{{ $hospitals->previousPageUrl() }}" aria-label="{{ __('bloodcare.national.common.previous') }}"><i class="la la-angle-left"></i></a>@endif
                        @foreach($hospitals->getUrlRange(max(1, $hospitals->currentPage() - 2), min($hospitals->lastPage(), $hospitals->currentPage() + 2)) as $page => $url)<a href="{{ $url }}" class="{{ $page === $hospitals->currentPage() ? 'is-active' : '' }}" @if($page === $hospitals->currentPage()) aria-current="page" @endif>{{ $page }}</a>@endforeach
                        @if($hospitals->hasMorePages())<a class="bc-lab-page-arrow" href="{{ $hospitals->nextPageUrl() }}" aria-label="{{ __('bloodcare.national.common.next') }}"><i class="la la-angle-right"></i></a>@else<span class="bc-lab-page-arrow is-disabled" aria-hidden="true"><i class="la la-angle-right"></i></span>@endif
                    </nav>
                </footer>
            @endif
        </section>
    </div>
</div>

@foreach($hospitals as $hospital)
    <div class="bc-modal" data-bc-hospital-modal="details-{{ $hospital->id }}" hidden>
        <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.national.hospitals.close') }}"></button>
        <section class="bc-modal-dialog bc-hospital-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="bc-hospital-details-title-{{ $hospital->id }}" tabindex="-1">
            <header class="bc-modal-header"><div><p class="bc-eyebrow">{{ $hospital->code }}</p><h2 id="bc-hospital-details-title-{{ $hospital->id }}">{{ __('bloodcare.national.hospitals.details_title') }}</h2><p>{{ __('bloodcare.national.hospitals.details_help') }}</p></div><button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.national.hospitals.close') }}"><i class="la la-times"></i></button></header>
            <div class="bc-hospital-modal-body">
                <dl class="bc-hospital-detail-grid">
                    <div><dt>{{ __('bloodcare.national.hospitals.code') }}</dt><dd>{{ $hospital->code }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.common.status') }}</dt><dd><span class="bc-status bc-status-{{ $hospital->is_active ? 'active' : 'inactive' }}">{{ $hospital->is_active ? __('bloodcare.national.common.active') : __('bloodcare.national.common.inactive') }}</span></dd></div>
                    <div class="bc-hospital-detail-wide"><dt>{{ __('bloodcare.national.hospitals.name') }}</dt><dd>{{ $hospital->name }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.hospitals.region') }}</dt><dd>{{ $regionLabels[$hospital->region] ?? ($hospital->region ?: '—') }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.hospitals.phone') }}</dt><dd>{{ $hospital->phone ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                    <div class="bc-hospital-detail-wide"><dt>{{ __('bloodcare.national.hospitals.address') }}</dt><dd>{{ $hospital->address ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.hospitals.request_count') }}</dt><dd>{{ $hospital->blood_requests_count }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.hospitals.created_at') }}</dt><dd>{{ $hospital->created_at?->format('Y-m-d H:i') ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                    <div class="bc-hospital-detail-wide"><dt>{{ __('bloodcare.national.hospitals.portal_accounts') }}</dt><dd class="bc-hospital-portal-list">@forelse($hospital->users as $portalUser)<span><strong>{{ $portalUser->name }}</strong><small>{{ $portalUser->email }}@if($portalUser->phone) · {{ $portalUser->phone }}@endif</small></span>@empty{{ __('bloodcare.national.common.not_provided') }}@endforelse</dd></div>
                </dl>
            </div>
            <footer class="bc-modal-footer"><button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.national.hospitals.close') }}</button></footer>
        </section>
    </div>

    <div class="bc-modal" data-bc-hospital-modal="edit-{{ $hospital->id }}" hidden>
        <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.national.hospitals.cancel') }}"></button>
        <section class="bc-modal-dialog bc-hospital-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="bc-hospital-edit-title-{{ $hospital->id }}" tabindex="-1">
            <header class="bc-modal-header"><div><p class="bc-eyebrow">{{ $hospital->code }}</p><h2 id="bc-hospital-edit-title-{{ $hospital->id }}">{{ __('bloodcare.national.hospitals.edit_title') }}</h2><p>{{ __('bloodcare.national.hospitals.edit_help') }}</p></div><button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.national.hospitals.cancel') }}"><i class="la la-times"></i></button></header>
            <form method="POST" action="{{ route('bloodcare.admin.hospitals.update', $hospital) }}" class="bc-national-form bc-hospital-edit-form">@csrf @method('PATCH')
                <label>{{ __('bloodcare.national.hospitals.name') }}<input name="name" value="{{ $hospital->name }}" required maxlength="180" placeholder="{{ __('bloodcare.national.hospitals.name_placeholder') }}"></label>
                <div class="bc-hospital-region-field">
                    <label id="bc-hospital-edit-region-label-{{ $hospital->id }}" for="bc-hospital-edit-region-{{ $hospital->id }}">{{ __('bloodcare.national.hospitals.region') }}</label>
                    <div class="bc-filter-dropdown bc-lab-filter-dropdown bc-hospital-region-dropdown" data-bc-national-select>
                        <i class="la la-map-marker bc-filter-dropdown-icon" aria-hidden="true"></i>
                        <select class="visually-hidden" id="bc-hospital-edit-region-{{ $hospital->id }}" name="region" tabindex="-1" aria-labelledby="bc-hospital-edit-region-label-{{ $hospital->id }}">
                            <option value="">{{ __('bloodcare.national.hospitals.select_region') }}</option>
                            @foreach($regionLabels as $region => $label)<option value="{{ $region }}" @selected($hospital->region === $region)>{{ $label }}</option>@endforeach
                        </select>
                        <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="bc-hospital-edit-region-menu-{{ $hospital->id }}" aria-required="true"><span data-bc-select-label>{{ $regionLabels[$hospital->region] ?? __('bloodcare.national.hospitals.select_region') }}</span><i class="la la-angle-down" aria-hidden="true"></i></button>
                        <div class="bc-filter-dropdown-menu" id="bc-hospital-edit-region-menu-{{ $hospital->id }}" role="listbox" aria-labelledby="bc-hospital-edit-region-label-{{ $hospital->id }}" hidden>
                            <button type="button" role="option" data-value="" aria-selected="false"><span>{{ __('bloodcare.national.hospitals.select_region') }}</span><i class="la la-check" aria-hidden="true"></i></button>
                            @foreach($regionLabels as $region => $label)<button type="button" role="option" data-value="{{ $region }}" aria-selected="{{ $hospital->region === $region ? 'true' : 'false' }}"><span>{{ $label }}</span><i class="la la-check" aria-hidden="true"></i></button>@endforeach
                        </div>
                    </div>
                </div>
                <label>{{ __('bloodcare.national.hospitals.address') }}<input name="address" value="{{ $hospital->address }}" maxlength="255" placeholder="{{ __('bloodcare.national.hospitals.address_placeholder') }}"></label>
                <label>{{ __('bloodcare.national.hospitals.phone') }}<input name="phone" value="{{ $hospital->phone }}" maxlength="30" placeholder="{{ __('bloodcare.national.hospitals.phone_placeholder') }}"></label>
                <label class="bc-hospital-active-toggle"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($hospital->is_active)><span>{{ __('bloodcare.national.hospitals.active_label') }}</span></label>
                <footer class="bc-modal-footer"><button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.national.hospitals.cancel') }}</button><button class="btn bc-btn-primary" type="submit"><i class="la la-save" aria-hidden="true"></i> {{ __('bloodcare.national.hospitals.save_changes') }}</button></footer>
            </form>
        </section>
    </div>

    <div class="bc-modal" data-bc-hospital-modal="delete-{{ $hospital->id }}" hidden>
        <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.national.hospitals.cancel') }}"></button>
        <section class="bc-modal-dialog bc-modal-dialog-small bc-hospital-delete-dialog" role="alertdialog" aria-modal="true" aria-labelledby="bc-hospital-delete-title-{{ $hospital->id }}" tabindex="-1">
            <header class="bc-modal-header"><div><p class="bc-eyebrow">{{ $hospital->code }}</p><h2 id="bc-hospital-delete-title-{{ $hospital->id }}">{{ __('bloodcare.national.hospitals.delete_title') }}</h2></div><button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.national.hospitals.cancel') }}"><i class="la la-times"></i></button></header>
            <div class="bc-hospital-delete-body"><span class="bc-hospital-delete-icon"><i class="la la-trash" aria-hidden="true"></i></span>@if($hospital->blood_requests_count > 0)<p>{{ __('bloodcare.national.hospitals.delete_blocked') }}</p>@else<p>{{ __('bloodcare.national.hospitals.delete_confirm', ['hospital' => $hospital->name]) }}</p>@endif</div>
            <footer class="bc-modal-footer"><button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.national.hospitals.cancel') }}</button>@if($hospital->blood_requests_count === 0)<form method="POST" action="{{ route('bloodcare.admin.hospitals.destroy', $hospital) }}">@csrf @method('DELETE')<button class="btn btn-danger" type="submit"><i class="la la-trash" aria-hidden="true"></i> {{ __('bloodcare.national.hospitals.delete') }}</button></form>@endif</footer>
        </section>
    </div>
@endforeach
@endsection
