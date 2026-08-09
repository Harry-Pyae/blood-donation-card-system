@extends(backpack_view('blank'))

@section('title', __('bloodcare.national.laboratory.title'))
@push('before_styles') @include('admin.partials.favicon') @endpush
@push('after_styles') <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}?v={{ filemtime(public_path('css/bloodcare-admin.css')) }}"> @endpush
@push('after_scripts')
<script src="{{ asset('js/bloodcare-national-filters.js') }}?v={{ filemtime(public_path('js/bloodcare-national-filters.js')) }}" defer></script>
<script src="{{ asset('js/bloodcare-clinical-workflows.js') }}?v={{ filemtime(public_path('js/bloodcare-clinical-workflows.js')) }}" defer></script>
@endpush

@section('content')
<div class="bc-admin-page bc-module-page bc-national-page bc-laboratory-page">
    <header class="bc-page-heading">
        <div class="bc-module-title"><span class="bc-module-icon"><i class="la la-vials"></i></span><div>
            <p class="bc-eyebrow">{{ __('bloodcare.national.laboratory.eyebrow') }}</p>
            <h1>{{ __('bloodcare.national.laboratory.title') }}</h1>
            <p>{{ __('bloodcare.national.laboratory.description') }}</p>
        </div></div>
        @include('admin.partials.utility-controls')
    </header>

    @if(session('status'))<div class="alert alert-success bc-lab-alert">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger bc-lab-alert">{{ $errors->first() }}</div>@endif

    <section class="bc-module-metrics bc-lab-metrics" aria-label="{{ __('bloodcare.national.laboratory.summary') }}">
        <article class="bc-mini-metric"><span class="bc-mini-dot bc-dot-blue"></span><div><small>{{ __('bloodcare.national.laboratory.accepted') }}</small><strong>{{ $metrics['total'] }}</strong></div></article>
        <article class="bc-mini-metric"><span class="bc-mini-dot bc-dot-amber"></span><div><small>{{ __('bloodcare.national.laboratory.awaiting') }}</small><strong>{{ $metrics['quarantined'] }}</strong></div></article>
        <article class="bc-mini-metric"><span class="bc-mini-dot bc-dot-green"></span><div><small>{{ __('bloodcare.national.laboratory.released') }}</small><strong>{{ $metrics['released'] }}</strong></div></article>
        <article class="bc-mini-metric"><span class="bc-mini-dot bc-dot-red"></span><div><small>{{ __('bloodcare.national.laboratory.discarded') }}</small><strong>{{ $metrics['discarded'] }}</strong></div></article>
    </section>

    <section class="bc-panel bc-module-table-panel bc-lab-table-panel">
        <div class="bc-panel-heading bc-lab-panel-heading"><div><h2>{{ __('bloodcare.national.laboratory.queue_title') }}</h2><p>{{ __('bloodcare.national.laboratory.queue_help') }}</p></div><span class="bc-lab-privacy-badge"><i class="la la-user-shield"></i> {{ __('bloodcare.national.laboratory.privacy') }}</span></div>

        <form class="bc-lab-filter-bar" method="GET" action="{{ route('bloodcare.lab.laboratory') }}">
            <label class="bc-lab-search" for="bc-lab-search"><i class="la la-search" aria-hidden="true"></i><input id="bc-lab-search" name="q" type="search" value="{{ $search }}" placeholder="{{ __('bloodcare.national.laboratory.search_placeholder') }}" autocomplete="off"></label>
            <div class="bc-filter-dropdown bc-lab-filter-dropdown" data-bc-national-select>
                <i class="la la-tint bc-filter-dropdown-icon" aria-hidden="true"></i>
                <select class="visually-hidden" id="bc-lab-group" name="group" tabindex="-1" aria-hidden="true"><option value="all" @selected($group === 'all')>{{ __('bloodcare.national.common.all_blood_groups') }}</option>@foreach($bloodGroups as $bloodGroup)<option value="{{ $bloodGroup }}" @selected($group === $bloodGroup)>{{ $bloodGroup }}</option>@endforeach</select>
                <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="bc-lab-group-menu"><span data-bc-select-label>{{ $group === 'all' ? __('bloodcare.national.common.all_blood_groups') : $group }}</span><i class="la la-angle-down" aria-hidden="true"></i></button>
                <div class="bc-filter-dropdown-menu" id="bc-lab-group-menu" role="listbox" aria-label="{{ __('bloodcare.national.common.blood_group') }}" hidden><button type="button" role="option" data-value="all" aria-selected="{{ $group === 'all' ? 'true' : 'false' }}"><span>{{ __('bloodcare.national.common.all_blood_groups') }}</span><i class="la la-check" aria-hidden="true"></i></button>@foreach($bloodGroups as $bloodGroup)<button type="button" role="option" data-value="{{ $bloodGroup }}" aria-selected="{{ $group === $bloodGroup ? 'true' : 'false' }}"><span>{{ $bloodGroup }}</span><i class="la la-check" aria-hidden="true"></i></button>@endforeach</div>
            </div>
            <div class="bc-filter-dropdown bc-lab-filter-dropdown" data-bc-national-select>
                <i class="la la-shield-alt bc-filter-dropdown-icon" aria-hidden="true"></i>
                <select class="visually-hidden" id="bc-lab-safety" name="safety" tabindex="-1" aria-hidden="true"><option value="all" @selected($safety === 'all')>{{ __('bloodcare.national.laboratory.all_safety') }}</option><option value="quarantined" @selected($safety === 'quarantined')>{{ __('bloodcare.national.laboratory.quarantined') }}</option><option value="released" @selected($safety === 'released')>{{ __('bloodcare.national.laboratory.released') }}</option><option value="discarded" @selected($safety === 'discarded')>{{ __('bloodcare.national.laboratory.discarded') }}</option></select>
                <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="bc-lab-safety-menu"><span data-bc-select-label>{{ __('bloodcare.national.laboratory.'.($safety === 'all' ? 'all_safety' : $safety)) }}</span><i class="la la-angle-down" aria-hidden="true"></i></button>
                <div class="bc-filter-dropdown-menu" id="bc-lab-safety-menu" role="listbox" aria-label="{{ __('bloodcare.national.laboratory.safety_status') }}" hidden>@foreach(['all' => 'all_safety', 'quarantined' => 'quarantined', 'released' => 'released', 'discarded' => 'discarded'] as $value => $key)<button type="button" role="option" data-value="{{ $value }}" aria-selected="{{ $safety === $value ? 'true' : 'false' }}"><span>{{ __('bloodcare.national.laboratory.'.$key) }}</span><i class="la la-check" aria-hidden="true"></i></button>@endforeach</div>
            </div>
            <button class="btn bc-btn-primary bc-lab-filter-submit" type="submit"><i class="la la-filter"></i> {{ __('bloodcare.national.common.apply') }}</button>
            @if($search !== '' || $group !== 'all' || $safety !== 'all')<a class="btn bc-btn-outline bc-lab-filter-reset" href="{{ route('bloodcare.lab.laboratory') }}">{{ __('bloodcare.national.common.clear') }}</a>@endif
        </form>

        <div class="table-responsive"><table class="table table-vcenter bc-module-table bc-laboratory-table"><thead><tr>
            <th>{{ __('bloodcare.national.laboratory.donation_unit') }}</th><th>{{ __('bloodcare.national.common.blood_group') }}</th><th>{{ __('bloodcare.national.laboratory.collected') }}</th><th>{{ __('bloodcare.national.laboratory.safety_status') }}</th><th>{{ __('bloodcare.national.laboratory.actions') }}</th>
        </tr></thead><tbody>
        @forelse($donations as $donation)
            @php $test = $donation->labTest; $releaseStatus = $test?->release_status ?? 'quarantined'; $unitNumber = $donation->bloodUnit?->unit_number ?? $donation->bag_unit_number; @endphp
            <tr>
                <td><strong>{{ $donation->reference }}</strong><br><small>{{ $unitNumber ?: __('bloodcare.national.laboratory.unit_missing') }}</small></td>
                <td><span class="bc-group-badge">{{ $donation->blood_group }}</span></td>
                <td>{{ $donation->donation_date?->format('d M Y') ?? '—' }}</td>
                <td><span class="bc-status bc-status-{{ $releaseStatus }}">{{ __('bloodcare.national.laboratory.'.$releaseStatus) }}</span></td>
                <td><button class="bc-hospital-action-button" type="button" data-bc-clinical-open="lab-details-{{ $donation->id }}" title="{{ __('bloodcare.national.laboratory.view_details') }}" aria-label="{{ __('bloodcare.national.laboratory.view_details') }}"><i class="la la-eye" aria-hidden="true"></i></button></td>
            </tr>
        @empty<tr><td colspan="5" class="text-center py-5">{{ __('bloodcare.national.laboratory.empty_text') }}</td></tr>@endforelse
        </tbody></table></div>

        @if($donations->total() > 0)<footer class="bc-lab-pagination"><span>{{ __('bloodcare.national.common.showing', ['from' => $donations->firstItem(), 'to' => $donations->lastItem(), 'total' => $donations->total()]) }}</span><nav aria-label="{{ __('bloodcare.national.laboratory.pages') }}">@if($donations->onFirstPage())<span class="bc-lab-page-arrow is-disabled" aria-hidden="true"><i class="la la-angle-left"></i></span>@else<a class="bc-lab-page-arrow" href="{{ $donations->previousPageUrl() }}" aria-label="{{ __('bloodcare.national.common.previous') }}"><i class="la la-angle-left"></i></a>@endif @foreach($donations->getUrlRange(max(1, $donations->currentPage() - 2), min($donations->lastPage(), $donations->currentPage() + 2)) as $page => $url)<a href="{{ $url }}" class="{{ $page === $donations->currentPage() ? 'is-active' : '' }}" @if($page === $donations->currentPage()) aria-current="page" @endif>{{ $page }}</a>@endforeach @if($donations->hasMorePages())<a class="bc-lab-page-arrow" href="{{ $donations->nextPageUrl() }}" aria-label="{{ __('bloodcare.national.common.next') }}"><i class="la la-angle-right"></i></a>@else<span class="bc-lab-page-arrow is-disabled" aria-hidden="true"><i class="la la-angle-right"></i></span>@endif</nav></footer>@endif
    </section>
</div>

@foreach($donations as $donation)
    @php
        $test = $donation->labTest;
        $releaseStatus = $test?->release_status ?? 'quarantined';
        $unitNumber = $donation->bloodUnit?->unit_number ?? $donation->bag_unit_number;
        $mandatoryTtiFields = ['hiv_status' => 'hiv', 'hepatitis_b_status' => 'hepatitis_b', 'hepatitis_c_status' => 'hepatitis_c', 'syphilis_status' => 'syphilis'];
        $regionalTtiFields = ['htlv_status' => 'htlv', 'malaria_status' => 'malaria', 'chagas_status' => 'chagas', 'west_nile_status' => 'west_nile', 'zika_status' => 'zika'];
        $resultFields = $mandatoryTtiFields + ['antibody_screen_status' => 'antibody_screen'] + $regionalTtiFields;
        $reactiveTests = $test ? collect($resultFields)->filter(fn ($label, $field) => $test->{$field} === 'reactive')->values() : collect();
        $groupMismatch = $test && $test->confirmed_blood_group !== $donation->blood_group;
        $expectedRhd = str_ends_with($donation->blood_group, '+') ? 'positive' : 'negative';
        $rhdMismatch = $test && $test->rhd_type && $test->rhd_type !== $expectedRhd;
    @endphp
    <div class="bc-modal" data-bc-clinical-modal="lab-details-{{ $donation->id }}" hidden>
        <button class="bc-modal-backdrop" type="button" data-bc-clinical-close aria-label="{{ __('bloodcare.national.laboratory.close') }}"></button>
        <section class="bc-modal-dialog bc-clinical-dialog bc-lab-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="bc-lab-detail-title-{{ $donation->id }}" tabindex="-1">
            <header class="bc-modal-header"><div><p class="bc-eyebrow">{{ $unitNumber ?: $donation->reference }}</p><h2 id="bc-lab-detail-title-{{ $donation->id }}">{{ __('bloodcare.national.laboratory.details_title') }}</h2><p>{{ __('bloodcare.national.laboratory.details_help') }}</p></div><button class="bc-modal-close" type="button" data-bc-clinical-close aria-label="{{ __('bloodcare.national.laboratory.close') }}"><i class="la la-times"></i></button></header>
            @if($test)
                <div class="bc-clinical-modal-scroll">
                    <dl class="bc-clinical-detail-grid">
                        <div><dt>{{ __('bloodcare.national.laboratory.donation_unit') }}</dt><dd>{{ $donation->reference }}<br><small>{{ $unitNumber }}</small></dd></div>
                        <div><dt>{{ __('bloodcare.national.laboratory.lab_reference') }}</dt><dd>{{ $test->reference }}</dd></div>
                        <div><dt>{{ __('bloodcare.national.common.blood_group') }}</dt><dd>{{ $donation->blood_group }} → {{ $test->confirmed_blood_group }}</dd></div>
                        <div><dt>{{ __('bloodcare.national.laboratory.rhd_typing') }}</dt><dd>{{ $test->rhd_type ? __('bloodcare.national.laboratory.rhd_'.$test->rhd_type) : __('bloodcare.national.laboratory.not_tested') }}</dd></div>
                        <div><dt>{{ __('bloodcare.national.laboratory.safety_status') }}</dt><dd><span class="bc-status bc-status-{{ $releaseStatus }}">{{ __('bloodcare.national.laboratory.'.$releaseStatus) }}</span></dd></div>
                        <div><dt>{{ __('bloodcare.national.laboratory.tested_by') }}</dt><dd>{{ $test->testedBy?->name ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                        <div><dt>{{ __('bloodcare.national.laboratory.tested_at') }}</dt><dd>{{ $test->tested_at?->format('d M Y, H:i') ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                    </dl>
                    <h3 class="bc-clinical-section-title">{{ __('bloodcare.national.laboratory.mandatory_tti') }}</h3>
                    <div class="bc-lab-result-grid">@foreach($mandatoryTtiFields as $field => $labelKey) @php $result = $test->{$field} ?: 'not_tested'; @endphp <div class="bc-lab-result-card bc-lab-result-{{ $result }}"><span>{{ __('bloodcare.national.laboratory.tests.'.$labelKey) }}</span><strong><i class="la {{ $result === 'negative' ? 'la-check-circle' : 'la-exclamation-triangle' }}" aria-hidden="true"></i> {{ __('bloodcare.national.laboratory.'.$result) }}</strong></div>@endforeach</div>
                    <h3 class="bc-clinical-section-title">{{ __('bloodcare.national.laboratory.immunohematology') }}</h3>
                    @php $antibodyResult = $test->antibody_screen_status ?: 'not_tested'; @endphp
                    <div class="bc-lab-result-grid bc-lab-immuno-grid">
                        <div class="bc-lab-result-card"><span>{{ __('bloodcare.national.laboratory.rhd_typing') }}</span><strong>{{ $test->rhd_type ? __('bloodcare.national.laboratory.rhd_'.$test->rhd_type) : __('bloodcare.national.laboratory.not_tested') }}</strong></div>
                        <div class="bc-lab-result-card bc-lab-result-{{ $antibodyResult }}"><span>{{ __('bloodcare.national.laboratory.tests.antibody_screen') }}</span><strong><i class="la {{ $antibodyResult === 'negative' ? 'la-check-circle' : 'la-exclamation-triangle' }}" aria-hidden="true"></i> {{ __('bloodcare.national.laboratory.'.$antibodyResult) }}</strong></div>
                    </div>
                    <div class="bc-component-section-heading"><h3 class="bc-clinical-section-title">{{ __('bloodcare.national.laboratory.regional_tti') }}</h3><span>{{ __('bloodcare.national.laboratory.regional_tti_help') }}</span></div>
                    <div class="bc-lab-result-grid">@foreach($regionalTtiFields as $field => $labelKey) @php $result = $test->{$field} ?: 'not_required'; @endphp <div class="bc-lab-result-card bc-lab-result-{{ $result }}"><span>{{ __('bloodcare.national.laboratory.tests.'.$labelKey) }}</span><strong><i class="la {{ $result === 'negative' ? 'la-check-circle' : ($result === 'reactive' ? 'la-exclamation-triangle' : 'la-minus-circle') }}" aria-hidden="true"></i> {{ __('bloodcare.national.laboratory.'.$result) }}</strong></div>@endforeach</div>
                    @if($releaseStatus === 'discarded')
                        <div class="bc-lab-discard-reason"><i class="la la-exclamation-triangle" aria-hidden="true"></i><div><strong>{{ __('bloodcare.national.laboratory.discard_reason') }}</strong>@if($reactiveTests->isNotEmpty())<p>{{ __('bloodcare.national.laboratory.discard_reactive', ['tests' => $reactiveTests->map(fn ($key) => __('bloodcare.national.laboratory.tests.'.$key))->join(', ')]) }}</p>@endif @if($groupMismatch)<p>{{ __('bloodcare.national.laboratory.discard_group_mismatch') }}</p>@endif @if($rhdMismatch)<p>{{ __('bloodcare.national.laboratory.discard_rhd_mismatch') }}</p>@endif</div></div>
                    @endif
                    <div class="bc-clinical-note-card"><strong>{{ __('bloodcare.national.laboratory.lab_notes') }}</strong><p>{{ $test->notes ?: __('bloodcare.national.common.not_provided') }}</p></div>
                </div>
                <footer class="bc-modal-footer"><button class="btn bc-btn-outline" type="button" data-bc-clinical-close>{{ __('bloodcare.national.laboratory.close') }}</button></footer>
            @else
                <form method="POST" action="{{ route('bloodcare.lab.laboratory.store', $donation) }}" class="bc-lab-modal-form">@csrf
                    <div class="bc-clinical-modal-scroll">
                        <div class="bc-component-staged-note bc-lab-safety-note"><i class="la la-shield-alt" aria-hidden="true"></i><span>{{ __('bloodcare.national.laboratory.record_results_help') }}</span></div>
                        <div class="bc-lab-modal-fields">
                            <h3 class="bc-lab-form-section-title">{{ __('bloodcare.national.laboratory.mandatory_tti') }}</h3>
                            @foreach($mandatoryTtiFields as $field => $labelKey)
                                @php $fieldId = 'bc-lab-'.$donation->id.'-'.$field; @endphp
                                <div class="bc-clinical-field"><label id="{{ $fieldId }}-label" for="{{ $fieldId }}">{{ __('bloodcare.national.laboratory.tests.'.$labelKey) }}</label><div class="bc-filter-dropdown bc-clinical-select" data-bc-national-select>
                                    <select class="visually-hidden" id="{{ $fieldId }}" name="{{ $field }}" tabindex="-1" aria-labelledby="{{ $fieldId }}-label"><option value="negative">{{ __('bloodcare.national.laboratory.negative') }}</option><option value="reactive">{{ __('bloodcare.national.laboratory.reactive') }}</option></select>
                                    <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="{{ $fieldId }}-menu"><span data-bc-select-label>{{ __('bloodcare.national.laboratory.negative') }}</span><i class="la la-angle-down" aria-hidden="true"></i></button>
                                    <div class="bc-filter-dropdown-menu" id="{{ $fieldId }}-menu" role="listbox" aria-labelledby="{{ $fieldId }}-label" hidden><button type="button" role="option" data-value="negative" aria-selected="true"><span>{{ __('bloodcare.national.laboratory.negative') }}</span><i class="la la-check" aria-hidden="true"></i></button><button type="button" role="option" data-value="reactive" aria-selected="false"><span>{{ __('bloodcare.national.laboratory.reactive') }}</span><i class="la la-check" aria-hidden="true"></i></button></div>
                                </div></div>
                            @endforeach
                            <h3 class="bc-lab-form-section-title">{{ __('bloodcare.national.laboratory.immunohematology') }}</h3>
                            @php $groupId = 'bc-lab-'.$donation->id.'-confirmed-group'; @endphp
                            <div class="bc-clinical-field"><label id="{{ $groupId }}-label" for="{{ $groupId }}">{{ __('bloodcare.national.laboratory.confirmed_group') }}</label><div class="bc-filter-dropdown bc-clinical-select" data-bc-national-select>
                                <select class="visually-hidden" id="{{ $groupId }}" name="confirmed_blood_group" tabindex="-1" aria-labelledby="{{ $groupId }}-label">@foreach($bloodGroups as $bloodGroup)<option value="{{ $bloodGroup }}" @selected($donation->blood_group === $bloodGroup)>{{ $bloodGroup }}</option>@endforeach</select>
                                <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="{{ $groupId }}-menu"><span data-bc-select-label>{{ $donation->blood_group }}</span><i class="la la-angle-down" aria-hidden="true"></i></button>
                                <div class="bc-filter-dropdown-menu" id="{{ $groupId }}-menu" role="listbox" aria-labelledby="{{ $groupId }}-label" hidden>@foreach($bloodGroups as $bloodGroup)<button type="button" role="option" data-value="{{ $bloodGroup }}" aria-selected="{{ $donation->blood_group === $bloodGroup ? 'true' : 'false' }}"><span>{{ $bloodGroup }}</span><i class="la la-check" aria-hidden="true"></i></button>@endforeach</div>
                            </div></div>
                            @php $rhdId = 'bc-lab-'.$donation->id.'-rhd'; $defaultRhd = str_ends_with($donation->blood_group, '+') ? 'positive' : 'negative'; @endphp
                            <div class="bc-clinical-field"><label id="{{ $rhdId }}-label" for="{{ $rhdId }}">{{ __('bloodcare.national.laboratory.rhd_typing') }}</label><div class="bc-filter-dropdown bc-clinical-select" data-bc-national-select>
                                <select class="visually-hidden" id="{{ $rhdId }}" name="rhd_type" tabindex="-1" aria-labelledby="{{ $rhdId }}-label"><option value="positive" @selected($defaultRhd === 'positive')>{{ __('bloodcare.national.laboratory.rhd_positive') }}</option><option value="negative" @selected($defaultRhd === 'negative')>{{ __('bloodcare.national.laboratory.rhd_negative') }}</option></select>
                                <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="{{ $rhdId }}-menu"><span data-bc-select-label>{{ __('bloodcare.national.laboratory.rhd_'.$defaultRhd) }}</span><i class="la la-angle-down" aria-hidden="true"></i></button>
                                <div class="bc-filter-dropdown-menu" id="{{ $rhdId }}-menu" role="listbox" aria-labelledby="{{ $rhdId }}-label" hidden>@foreach(['positive','negative'] as $value)<button type="button" role="option" data-value="{{ $value }}" aria-selected="{{ $defaultRhd === $value ? 'true' : 'false' }}"><span>{{ __('bloodcare.national.laboratory.rhd_'.$value) }}</span><i class="la la-check" aria-hidden="true"></i></button>@endforeach</div>
                            </div></div>
                            @php $antibodyId = 'bc-lab-'.$donation->id.'-antibody'; @endphp
                            <div class="bc-clinical-field"><label id="{{ $antibodyId }}-label" for="{{ $antibodyId }}">{{ __('bloodcare.national.laboratory.tests.antibody_screen') }}</label><div class="bc-filter-dropdown bc-clinical-select" data-bc-national-select>
                                <select class="visually-hidden" id="{{ $antibodyId }}" name="antibody_screen_status" tabindex="-1" aria-labelledby="{{ $antibodyId }}-label"><option value="negative">{{ __('bloodcare.national.laboratory.negative') }}</option><option value="reactive">{{ __('bloodcare.national.laboratory.reactive') }}</option></select>
                                <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="{{ $antibodyId }}-menu"><span data-bc-select-label>{{ __('bloodcare.national.laboratory.negative') }}</span><i class="la la-angle-down" aria-hidden="true"></i></button>
                                <div class="bc-filter-dropdown-menu" id="{{ $antibodyId }}-menu" role="listbox" aria-labelledby="{{ $antibodyId }}-label" hidden><button type="button" role="option" data-value="negative" aria-selected="true"><span>{{ __('bloodcare.national.laboratory.negative') }}</span><i class="la la-check" aria-hidden="true"></i></button><button type="button" role="option" data-value="reactive" aria-selected="false"><span>{{ __('bloodcare.national.laboratory.reactive') }}</span><i class="la la-check" aria-hidden="true"></i></button></div>
                            </div></div>
                            <div class="bc-lab-form-section-heading"><h3 class="bc-lab-form-section-title">{{ __('bloodcare.national.laboratory.regional_tti') }}</h3><p>{{ __('bloodcare.national.laboratory.regional_tti_help') }}</p></div>
                            @foreach($regionalTtiFields as $field => $labelKey)
                                @php $fieldId = 'bc-lab-'.$donation->id.'-'.$field; @endphp
                                <div class="bc-clinical-field"><label id="{{ $fieldId }}-label" for="{{ $fieldId }}">{{ __('bloodcare.national.laboratory.tests.'.$labelKey) }}</label><div class="bc-filter-dropdown bc-clinical-select" data-bc-national-select>
                                    <select class="visually-hidden" id="{{ $fieldId }}" name="{{ $field }}" tabindex="-1" aria-labelledby="{{ $fieldId }}-label"><option value="not_required">{{ __('bloodcare.national.laboratory.not_required') }}</option><option value="negative">{{ __('bloodcare.national.laboratory.negative') }}</option><option value="reactive">{{ __('bloodcare.national.laboratory.reactive') }}</option></select>
                                    <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="{{ $fieldId }}-menu"><span data-bc-select-label>{{ __('bloodcare.national.laboratory.not_required') }}</span><i class="la la-angle-down" aria-hidden="true"></i></button>
                                    <div class="bc-filter-dropdown-menu" id="{{ $fieldId }}-menu" role="listbox" aria-labelledby="{{ $fieldId }}-label" hidden>@foreach(['not_required','negative','reactive'] as $value)<button type="button" role="option" data-value="{{ $value }}" aria-selected="{{ $value === 'not_required' ? 'true' : 'false' }}"><span>{{ __('bloodcare.national.laboratory.'.$value) }}</span><i class="la la-check" aria-hidden="true"></i></button>@endforeach</div>
                                </div></div>
                            @endforeach
                            <label class="bc-component-location-field bc-lab-note-field"><span>{{ __('bloodcare.national.laboratory.lab_note') }} <small>{{ __('bloodcare.national.laboratory.optional') }}</small></span><textarea name="notes" maxlength="2000" rows="3" placeholder="{{ __('bloodcare.national.laboratory.note_placeholder') }}"></textarea></label>
                        </div>
                        <div class="bc-lab-safety-copy"><i class="la la-shield-alt"></i><span>{{ __('bloodcare.national.laboratory.safety_copy') }}</span></div>
                    </div>
                    <footer class="bc-modal-footer"><button class="btn bc-btn-outline" type="button" data-bc-clinical-close>{{ __('bloodcare.national.laboratory.close') }}</button><button class="btn bc-btn-primary" type="submit"><i class="la la-shield-alt" aria-hidden="true"></i> {{ __('bloodcare.national.laboratory.save_lock') }}</button></footer>
                </form>
            @endif
        </section>
    </div>
@endforeach
@endsection
