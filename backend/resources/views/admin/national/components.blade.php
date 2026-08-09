@extends(backpack_view('blank'))
@section('title', __('bloodcare.national.components.title'))
@push('before_styles') @include('admin.partials.favicon') @endpush
@push('after_styles') <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}?v={{ filemtime(public_path('css/bloodcare-admin.css')) }}"> @endpush
@push('after_scripts')
<script src="{{ asset('js/bloodcare-national-filters.js') }}?v={{ filemtime(public_path('js/bloodcare-national-filters.js')) }}" defer></script>
<script src="{{ asset('js/bloodcare-clinical-workflows.js') }}?v={{ filemtime(public_path('js/bloodcare-clinical-workflows.js')) }}" defer></script>
@endpush
@section('content')
@php
    $componentSpecs = [
        'red_cells' => ['days' => 42, 'suffix' => 'RBC'],
        'plasma' => ['days' => 365, 'suffix' => 'PLS'],
        'platelets' => ['days' => 5, 'suffix' => 'PLT'],
        'cryoprecipitate' => ['days' => 365, 'suffix' => 'CRYO'],
    ];
    $primaryComponentTypes = ['red_cells', 'plasma', 'platelets'];
    $modifierFields = ['leukoreduced', 'irradiated', 'washed'];
@endphp
<div class="bc-admin-page bc-module-page bc-national-page bc-components-page">
    <header class="bc-page-heading">
        <div class="bc-module-title"><span class="bc-module-icon"><i class="la la-project-diagram"></i></span><div>
            <p class="bc-eyebrow">{{ __('bloodcare.national.components.eyebrow') }}</p>
            <h1>{{ __('bloodcare.national.components.title') }}</h1>
            <p>{{ __('bloodcare.national.components.description') }}</p>
        </div></div>
        @include('admin.partials.utility-controls')
    </header>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <section class="bc-panel bc-module-table-panel bc-components-panel">
        <form class="bc-lab-filter-bar" method="GET" action="{{ route('bloodcare.lab.components') }}">
            <label class="bc-lab-search" for="bc-components-search">
                <i class="la la-search" aria-hidden="true"></i>
                <input id="bc-components-search" name="q" type="search" value="{{ $search }}" placeholder="{{ __('bloodcare.national.components.search_placeholder') }}" autocomplete="off">
            </label>
            <div class="bc-filter-dropdown bc-lab-filter-dropdown" data-bc-national-select>
                <i class="la la-tint bc-filter-dropdown-icon" aria-hidden="true"></i>
                <select class="visually-hidden" id="bc-components-group" name="group" tabindex="-1" aria-hidden="true">
                    <option value="all" @selected($group === 'all')>{{ __('bloodcare.national.common.all_blood_groups') }}</option>
                    @foreach($bloodGroups as $bloodGroup)<option value="{{ $bloodGroup }}" @selected($group === $bloodGroup)>{{ $bloodGroup }}</option>@endforeach
                </select>
                <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="bc-components-group-menu">
                    <span data-bc-select-label>{{ $group === 'all' ? __('bloodcare.national.common.all_blood_groups') : $group }}</span><i class="la la-angle-down" aria-hidden="true"></i>
                </button>
                <div class="bc-filter-dropdown-menu" id="bc-components-group-menu" role="listbox" aria-label="{{ __('bloodcare.national.common.blood_group') }}" hidden>
                    <button type="button" role="option" data-value="all" aria-selected="{{ $group === 'all' ? 'true' : 'false' }}"><span>{{ __('bloodcare.national.common.all_blood_groups') }}</span><i class="la la-check" aria-hidden="true"></i></button>
                    @foreach($bloodGroups as $bloodGroup)<button type="button" role="option" data-value="{{ $bloodGroup }}" aria-selected="{{ $group === $bloodGroup ? 'true' : 'false' }}"><span>{{ $bloodGroup }}</span><i class="la la-check" aria-hidden="true"></i></button>@endforeach
                </div>
            </div>
            <div class="bc-filter-dropdown bc-lab-filter-dropdown" data-bc-national-select>
                <i class="la la-project-diagram bc-filter-dropdown-icon" aria-hidden="true"></i>
                <select class="visually-hidden" id="bc-components-processing" name="processing" tabindex="-1" aria-hidden="true">
                    <option value="all" @selected($processing === 'all')>{{ __('bloodcare.national.components.all_processing') }}</option>
                    <option value="unprocessed" @selected($processing === 'unprocessed')>{{ __('bloodcare.national.components.unprocessed') }}</option>
                    <option value="partial" @selected($processing === 'partial')>{{ __('bloodcare.national.components.partial') }}</option>
                    <option value="processed" @selected($processing === 'processed')>{{ __('bloodcare.national.components.processed') }}</option>
                </select>
                <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="bc-components-processing-menu">
                    <span data-bc-select-label>{{ __('bloodcare.national.components.'.($processing === 'all' ? 'all_processing' : $processing)) }}</span><i class="la la-angle-down" aria-hidden="true"></i>
                </button>
                <div class="bc-filter-dropdown-menu" id="bc-components-processing-menu" role="listbox" aria-label="{{ __('bloodcare.national.components.processing_state') }}" hidden>
                    @foreach(['all' => 'all_processing', 'unprocessed' => 'unprocessed', 'partial' => 'partial', 'processed' => 'processed'] as $value => $key)
                        <button type="button" role="option" data-value="{{ $value }}" aria-selected="{{ $processing === $value ? 'true' : 'false' }}"><span>{{ __('bloodcare.national.components.'.$key) }}</span><i class="la la-check" aria-hidden="true"></i></button>
                    @endforeach
                </div>
            </div>
            <button class="btn bc-btn-primary bc-lab-filter-submit" type="submit"><i class="la la-filter"></i> {{ __('bloodcare.national.common.apply') }}</button>
            @if($search !== '' || $group !== 'all' || $processing !== 'all')<a class="btn bc-btn-outline bc-lab-filter-reset" href="{{ route('bloodcare.lab.components') }}">{{ __('bloodcare.national.common.clear') }}</a>@endif
        </form>

        <div class="table-responsive"><table class="table table-vcenter bc-module-table bc-components-table"><thead><tr>
            <th>{{ __('bloodcare.national.components.parent_unit') }}</th>
            <th>{{ __('bloodcare.national.common.blood_group') }}</th>
            <th>{{ __('bloodcare.national.components.status') }}</th>
            <th>{{ __('bloodcare.national.components.components') }}</th>
            <th>{{ __('bloodcare.national.components.actions') }}</th>
        </tr></thead><tbody>
        @forelse($parents as $unit)
            @php
                $primaryComponents = $unit->components->whereIn('component_type', $primaryComponentTypes);
                $componentCount = $primaryComponents->count();
                $plasma = $primaryComponents->firstWhere('component_type', 'plasma');
                $cryo = $plasma?->components->firstWhere('component_type', 'cryoprecipitate');
                $parentExpired = $unit->expires_at?->lt(today()) ?? false;
                $legacyPartial = $unit->status === 'used' && $componentCount > 0 && $componentCount < 3;
                $processableState = in_array($unit->status, ['available', 'processing'], true) || $legacyPartial;
                $hasViablePrimary = collect($primaryComponentTypes)->contains(fn ($type) => ! $primaryComponents->contains('component_type', $type) && ! ($unit->collected_at?->copy()->addDays($componentSpecs[$type]['days'])->lt(today()) ?? true));
                $canProcessPrimary = (bool) $unit->released_at && ! $parentExpired && $componentCount < 3 && $processableState && $hasViablePrimary;
                $canProcessCryo = (bool) ($unit->released_at && $plasma && ! $cryo && $plasma->status === 'available' && $plasma->released_at && ! ($plasma->expires_at?->isPast() ?? true) && $plasma->allocations->isEmpty());
                $canProcess = $canProcessPrimary || $canProcessCryo;
                $stateKey = ! $unit->released_at ? 'lab_release_required' : (($parentExpired && ! $canProcessCryo) ? 'expired' : ($componentCount >= 3 ? ($canProcessCryo ? 'cryo_available' : 'fully_processed') : ($componentCount > 0 ? 'partially_processed' : 'ready')));
                $processingReason = $canProcess ? null : __('bloodcare.national.components.'.($stateKey === 'ready' ? 'processing_unavailable' : $stateKey));
                $summaryTypes = $primaryComponents->pluck('component_type');
                if ($cryo) $summaryTypes->push('cryoprecipitate');
            @endphp
            <tr>
                <td><strong>{{ $unit->unit_number }}</strong><br><small>{{ $unit->donation?->reference }}</small></td>
                <td><span class="bc-group-badge">{{ $unit->blood_group }}</span></td>
                <td><span class="bc-status bc-component-status bc-component-status-{{ $stateKey }}">{{ __('bloodcare.national.components.'.$stateKey) }}</span></td>
                <td><strong>{{ __('bloodcare.national.components.component_progress', ['count' => $componentCount]) }}</strong>@if($summaryTypes->isNotEmpty())<small class="bc-component-summary">{{ $summaryTypes->map(fn($type) => __('bloodcare.national.components.types.'.$type))->join(' · ') }}</small>@endif</td>
                <td><div class="bc-component-row-actions">
                    <button class="bc-hospital-action-button" type="button" data-bc-clinical-open="component-details-{{ $unit->id }}" title="{{ __('bloodcare.national.components.view_details') }}" aria-label="{{ __('bloodcare.national.components.view_details') }}"><i class="la la-eye" aria-hidden="true"></i></button>
                    @if($canProcess)<button class="btn bc-btn-primary btn-sm bc-component-process-button" type="button" data-bc-clinical-open="component-process-{{ $unit->id }}"><i class="la la-project-diagram" aria-hidden="true"></i> {{ __('bloodcare.national.components.process_components') }}</button>@else<button class="btn bc-btn-primary btn-sm bc-component-process-button is-disabled" type="button" disabled title="{{ $processingReason }}"><i class="la la-project-diagram" aria-hidden="true"></i> {{ __('bloodcare.national.components.process_components') }}</button>@endif
                </div></td>
            </tr>
        @empty<tr><td colspan="5" class="text-center py-5">{{ __('bloodcare.national.components.no_results') }}</td></tr>@endforelse
        </tbody></table></div>

        @if($parents->total() > 0)
            <footer class="bc-lab-pagination"><span>{{ __('bloodcare.national.common.showing', ['from' => $parents->firstItem(), 'to' => $parents->lastItem(), 'total' => $parents->total()]) }}</span><nav aria-label="{{ __('bloodcare.national.components.pages') }}">
                @if($parents->onFirstPage())<span class="bc-lab-page-arrow is-disabled" aria-hidden="true"><i class="la la-angle-left"></i></span>@else<a class="bc-lab-page-arrow" href="{{ $parents->previousPageUrl() }}" aria-label="{{ __('bloodcare.national.common.previous') }}"><i class="la la-angle-left"></i></a>@endif
                @foreach($parents->getUrlRange(max(1, $parents->currentPage() - 2), min($parents->lastPage(), $parents->currentPage() + 2)) as $page => $url)<a href="{{ $url }}" class="{{ $page === $parents->currentPage() ? 'is-active' : '' }}" @if($page === $parents->currentPage()) aria-current="page" @endif>{{ $page }}</a>@endforeach
                @if($parents->hasMorePages())<a class="bc-lab-page-arrow" href="{{ $parents->nextPageUrl() }}" aria-label="{{ __('bloodcare.national.common.next') }}"><i class="la la-angle-right"></i></a>@else<span class="bc-lab-page-arrow is-disabled" aria-hidden="true"><i class="la la-angle-right"></i></span>@endif
            </nav></footer>
        @endif
    </section>
</div>

@foreach($parents as $unit)
    @php
        $primaryComponents = $unit->components->whereIn('component_type', $primaryComponentTypes);
        $componentCount = $primaryComponents->count();
        $plasma = $primaryComponents->firstWhere('component_type', 'plasma');
        $cryo = $plasma?->components->firstWhere('component_type', 'cryoprecipitate');
        $parentExpired = $unit->expires_at?->lt(today()) ?? false;
        $legacyPartial = $unit->status === 'used' && $componentCount > 0 && $componentCount < 3;
        $processableState = in_array($unit->status, ['available', 'processing'], true) || $legacyPartial;
        $hasViablePrimary = collect($primaryComponentTypes)->contains(fn ($type) => ! $primaryComponents->contains('component_type', $type) && ! ($unit->collected_at?->copy()->addDays($componentSpecs[$type]['days'])->lt(today()) ?? true));
        $canProcessPrimary = (bool) $unit->released_at && ! $parentExpired && $componentCount < 3 && $processableState && $hasViablePrimary;
        $canProcessCryo = (bool) ($unit->released_at && $plasma && ! $cryo && $plasma->status === 'available' && $plasma->released_at && ! ($plasma->expires_at?->isPast() ?? true) && $plasma->allocations->isEmpty());
        $canProcess = $canProcessPrimary || $canProcessCryo;
        $detailComponents = $primaryComponents->flatMap(fn ($component) => $component->component_type === 'plasma' ? collect([$component])->concat($component->components) : collect([$component]));
        $processingReason = ! $unit->released_at ? __('bloodcare.national.components.lab_release_required') : (($parentExpired && ! $canProcessCryo) ? __('bloodcare.national.components.expired') : ($componentCount >= 3 && ! $canProcessCryo ? __('bloodcare.national.components.fully_processed') : __('bloodcare.national.components.processing_unavailable')));
    @endphp
    <div class="bc-modal" data-bc-clinical-modal="component-details-{{ $unit->id }}" hidden>
        <button class="bc-modal-backdrop" type="button" data-bc-clinical-close aria-label="{{ __('bloodcare.national.components.close') }}"></button>
        <section class="bc-modal-dialog bc-clinical-dialog" role="dialog" aria-modal="true" aria-labelledby="bc-component-details-title-{{ $unit->id }}" tabindex="-1">
            <header class="bc-modal-header"><div><p class="bc-eyebrow">{{ $unit->unit_number }}</p><h2 id="bc-component-details-title-{{ $unit->id }}">{{ __('bloodcare.national.components.details_title') }}</h2><p>{{ __('bloodcare.national.components.details_help') }}</p></div><button class="bc-modal-close" type="button" data-bc-clinical-close aria-label="{{ __('bloodcare.national.components.close') }}"><i class="la la-times"></i></button></header>
            <div class="bc-clinical-modal-scroll">
                <h3 class="bc-clinical-section-title">{{ __('bloodcare.national.components.parent_details') }}</h3>
                <dl class="bc-clinical-detail-grid">
                    <div><dt>{{ __('bloodcare.national.components.parent_unit') }}</dt><dd>{{ $unit->unit_number }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.common.blood_group') }}</dt><dd>{{ $unit->blood_group }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.components.donation_reference') }}</dt><dd>{{ $unit->donation?->reference ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.components.collected') }}</dt><dd>{{ $unit->collected_at?->format('d M Y') ?: '—' }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.components.expires') }}</dt><dd>{{ $unit->expires_at?->format('d M Y') ?: '—' }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.components.storage_location') }}</dt><dd>{{ $unit->storage_location }}</dd></div>
                </dl>

                <div class="bc-component-section-heading"><h3 class="bc-clinical-section-title">{{ __('bloodcare.national.components.components') }}</h3><span>{{ __('bloodcare.national.components.component_progress', ['count' => $componentCount]) }}</span></div>
                @if($detailComponents->isNotEmpty())
                    <div class="bc-component-child-list">
                        @foreach($detailComponents as $component)
                            @php $isExpired = $component->expires_at?->lt(today()) ?? false; $isNear = ! $isExpired && ($component->expires_at?->lte(today()->addDays(7)) ?? false); $activeModifiers = collect($modifierFields)->filter(fn ($field) => (bool) $component->{$field}); @endphp
                            <article class="bc-component-child-card {{ $isExpired ? 'is-expired' : ($isNear ? 'is-near-expiry' : '') }}">
                                <div class="bc-component-child-icon"><i class="la la-tint" aria-hidden="true"></i></div>
                                <div class="bc-component-child-main"><strong>{{ __('bloodcare.national.components.types.'.$component->component_type) }}</strong><span>{{ $component->unit_number }}</span>@if($component->component_type === 'cryoprecipitate')<small>{{ __('bloodcare.national.components.derived_from_plasma', ['unit' => $component->parent?->unit_number]) }}</small>@endif @if($activeModifiers->isNotEmpty())<span class="bc-component-modifier-badges">@foreach($activeModifiers as $modifier)<em>{{ __('bloodcare.national.components.modifiers.'.$modifier) }}</em>@endforeach</span>@endif</div>
                                <dl><div><dt>{{ __('bloodcare.national.components.expires') }}</dt><dd>{{ $component->expires_at?->format('d M Y') ?: '—' }}</dd></div><div><dt>{{ __('bloodcare.national.components.location') }}</dt><dd>{{ $component->storage_location }}</dd></div></dl>
                                @if($isExpired)<span class="bc-component-expiry-badge is-expired">{{ __('bloodcare.national.components.expired_badge') }}</span>@elseif($isNear)<span class="bc-component-expiry-badge">{{ __('bloodcare.national.components.near_expiry') }}</span>@endif
                            </article>
                        @endforeach
                    </div>
                @else<p class="bc-clinical-empty-note">{{ __('bloodcare.national.components.none_created') }}</p>@endif
            </div>
            <footer class="bc-modal-footer"><button class="btn bc-btn-outline" type="button" data-bc-clinical-close>{{ __('bloodcare.national.components.close') }}</button>@if($canProcess)<button class="btn bc-btn-primary" type="button" data-bc-clinical-open="component-process-{{ $unit->id }}"><i class="la la-project-diagram" aria-hidden="true"></i> {{ __('bloodcare.national.components.process_components') }}</button>@else<button class="btn bc-btn-primary is-disabled" type="button" disabled title="{{ $processingReason }}"><i class="la la-project-diagram" aria-hidden="true"></i> {{ __('bloodcare.national.components.process_components') }}</button>@endif</footer>
        </section>
    </div>

    @if($canProcess)
    <div class="bc-modal" data-bc-clinical-modal="component-process-{{ $unit->id }}" hidden>
        <button class="bc-modal-backdrop" type="button" data-bc-clinical-close aria-label="{{ __('bloodcare.national.components.cancel') }}"></button>
        <section class="bc-modal-dialog bc-clinical-dialog bc-component-process-dialog" role="dialog" aria-modal="true" aria-labelledby="bc-component-process-title-{{ $unit->id }}" tabindex="-1">
            <header class="bc-modal-header"><div><p class="bc-eyebrow">{{ $unit->unit_number }}</p><h2 id="bc-component-process-title-{{ $unit->id }}">{{ __('bloodcare.national.components.process_title') }}</h2><p>{{ __('bloodcare.national.components.process_help') }}</p></div><button class="bc-modal-close" type="button" data-bc-clinical-close aria-label="{{ __('bloodcare.national.components.cancel') }}"><i class="la la-times"></i></button></header>
            <form method="POST" action="{{ route('bloodcare.lab.components.store', $unit) }}" class="bc-component-process-form">@csrf
                <div class="bc-clinical-modal-scroll">
                    <div class="bc-component-staged-note"><i class="la la-shield-alt" aria-hidden="true"></i><span>{{ __('bloodcare.national.components.staged_help') }}</span></div>
                    <fieldset class="bc-component-choice-fieldset"><legend>{{ __('bloodcare.national.components.select_components') }}</legend><div class="bc-component-choice-grid">
                        @foreach($componentSpecs as $type => $spec)
                            @php
                                $isCryo = $type === 'cryoprecipitate';
                                $existing = $isCryo ? $cryo : $primaryComponents->firstWhere('component_type', $type);
                                $calculatedExpiry = $unit->collected_at?->copy()->addDays($spec['days']);
                                $windowClosed = $calculatedExpiry?->lt(today()) ?? true;
                                $sourceMissing = $isCryo && ! $plasma;
                                $sourceUnavailable = $isCryo && $plasma && ($plasma->status !== 'available' || ! $plasma->released_at || ($plasma->expires_at?->isPast() ?? true) || $plasma->allocations->isNotEmpty());
                                $choiceDisabled = $existing || $windowClosed || $sourceMissing || $sourceUnavailable;
                                $checkByDefault = ! $choiceDisabled && (! $isCryo || $componentCount >= 3);
                            @endphp
                            <label class="bc-component-choice {{ $choiceDisabled ? 'is-disabled' : '' }}">
                                <input type="checkbox" name="components[]" value="{{ $type }}" @checked($checkByDefault) @disabled($choiceDisabled)>
                                <span class="bc-component-choice-check"><i class="la la-check" aria-hidden="true"></i></span>
                                <span class="bc-component-choice-copy"><strong>{{ __('bloodcare.national.components.types.'.$type) }}</strong><small>{{ substr($unit->unit_number, 0, 34) }}-{{ $spec['suffix'] }}</small><small>{{ __('bloodcare.national.components.expires') }}: {{ $calculatedExpiry?->format('d M Y') }}</small></span>
                                @if($existing)<em>{{ __('bloodcare.national.components.already_created') }}</em>@elseif($sourceMissing)<em>{{ __('bloodcare.national.components.cryo_requires_plasma') }}</em>@elseif($sourceUnavailable)<em>{{ __('bloodcare.national.components.cryo_source_unavailable') }}</em>@elseif($windowClosed)<em>{{ __('bloodcare.national.components.expiry_window_closed') }}</em>@endif
                            </label>
                        @endforeach
                    </div></fieldset>
                    <fieldset class="bc-component-modifier-fieldset"><legend>{{ __('bloodcare.national.components.modifier_title') }}</legend><p>{{ __('bloodcare.national.components.modifier_help') }}</p><div class="bc-component-modifier-grid">
                        @foreach(['red_cells','platelets'] as $type)
                            @php $existing = $primaryComponents->firstWhere('component_type', $type); @endphp
                            <article class="bc-component-modifier-card {{ $existing ? 'is-disabled' : '' }}"><strong>{{ __('bloodcare.national.components.types.'.$type) }}</strong><div>
                                @foreach($modifierFields as $modifier)<label><input type="checkbox" name="modifiers[{{ $type }}][{{ $modifier }}]" value="1" @disabled($existing)><span>{{ __('bloodcare.national.components.modifiers.'.$modifier) }}</span></label>@endforeach
                            </div></article>
                        @endforeach
                    </div></fieldset>
                    <label class="bc-component-location-field"><span>{{ __('bloodcare.national.components.storage_location') }}</span><input name="location" required value="{{ $unit->storage_location }}" maxlength="120" placeholder="{{ __('bloodcare.national.components.location_placeholder') }}" autocomplete="off"></label>
                </div>
                <footer class="bc-modal-footer"><button class="btn bc-btn-outline" type="button" data-bc-clinical-close>{{ __('bloodcare.national.components.cancel') }}</button><button class="btn bc-btn-primary" type="submit"><i class="la la-project-diagram" aria-hidden="true"></i> {{ __('bloodcare.national.components.process_components') }}</button></footer>
            </form>
        </section>
    </div>
    @endif
@endforeach
@endsection
