@extends(backpack_view('blank'))
@section('title', __('bloodcare.national.requests.title'))
@push('before_styles') @include('admin.partials.favicon') @endpush
@push('after_styles') <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}?v={{ filemtime(public_path('css/bloodcare-admin.css')) }}"> @endpush
@push('after_scripts')
<script src="{{ asset('js/bloodcare-national-filters.js') }}?v={{ filemtime(public_path('js/bloodcare-national-filters.js')) }}" defer></script>
<script src="{{ asset('js/bloodcare-hospital-services.js') }}?v={{ filemtime(public_path('js/bloodcare-hospital-services.js')) }}" defer></script>
@endpush
@section('content')
<div class="bc-admin-page bc-module-page bc-national-page">
    <header class="bc-page-heading">
        <div class="bc-module-title"><span class="bc-module-icon"><i class="la la-ambulance"></i></span><div>
            <p class="bc-eyebrow">{{ __('bloodcare.national.requests.eyebrow') }}</p>
            <h1>{{ __('bloodcare.national.requests.title') }}</h1>
            <p>{{ __('bloodcare.national.requests.description') }}</p>
        </div></div>
        @include('admin.partials.utility-controls')
    </header>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <section class="bc-panel bc-module-table-panel">
        <form class="bc-lab-filter-bar bc-hospital-search-bar" method="GET" action="{{ route('bloodcare.admin.blood-requests') }}">
            <label class="bc-lab-search" for="bc-request-search">
                <i class="la la-search" aria-hidden="true"></i>
                <input id="bc-request-search" name="q" type="search" value="{{ $search }}" placeholder="{{ __('bloodcare.national.requests.search_placeholder') }}" autocomplete="off">
            </label>
            <button class="btn bc-btn-primary bc-lab-filter-submit" type="submit"><i class="la la-search" aria-hidden="true"></i> {{ __('bloodcare.national.common.search') }}</button>
            @if($search !== '')<a class="btn bc-btn-outline bc-lab-filter-reset" href="{{ route('bloodcare.admin.blood-requests') }}">{{ __('bloodcare.national.common.clear') }}</a>@endif
        </form>

        <div class="table-responsive"><table class="table table-vcenter bc-module-table bc-request-table">
            <thead><tr>
                <th>{{ __('bloodcare.national.requests.hospital_patient') }}</th>
                <th>{{ __('bloodcare.national.requests.need') }}</th>
                <th>{{ __('bloodcare.national.common.status') }}</th>
                <th>{{ __('bloodcare.national.requests.priority') }}</th>
                <th>{{ __('bloodcare.national.requests.actions') }}</th>
            </tr></thead>
            <tbody>
            @forelse($requests as $req)
                <tr>
                    <td><strong>{{ $req->hospital?->name }}</strong><br><small>{{ $req->patient_reference }}</small></td>
                    <td>{{ $req->blood_group }} · {{ __('bloodcare.national.components.types.'.$req->component_type) }} · {{ __('bloodcare.national.requests.units', ['count' => $req->quantity]) }}@php $requiredModifiers = collect(['leukoreduced','irradiated','washed'])->filter(fn ($modifier) => $req->{'requires_'.$modifier}); @endphp @if($requiredModifiers->isNotEmpty())<small class="bc-request-modifier-summary">{{ $requiredModifiers->map(fn ($modifier) => __('bloodcare.national.components.modifiers.'.$modifier))->join(' · ') }}</small>@endif</td>
                    <td><span class="bc-status bc-status-{{ $req->status }}">{{ __('bloodcare.national.common.'.$req->status) }}</span></td>
                    <td><span class="bc-priority bc-priority-{{ $req->priority }}">{{ __('bloodcare.national.portal.'.$req->priority) }}</span></td>
                    <td>
                        <div class="bc-request-actions">
                            <button class="bc-request-view-button" type="button" data-bc-request-open="{{ $req->id }}" aria-label="{{ __('bloodcare.national.requests.view_details') }}" title="{{ __('bloodcare.national.requests.view_details') }}"><i class="la la-eye" aria-hidden="true"></i></button>
                            @if($req->status === 'pending')
                                <form method="POST" action="{{ route('bloodcare.admin.blood-requests.review', $req) }}">@csrf @method('PATCH')<input type="hidden" name="decision" value="approve"><button class="btn btn-success btn-sm" type="submit">{{ __('bloodcare.national.requests.approve') }}</button></form>
                                <button class="btn btn-outline-danger btn-sm" type="button" data-bc-request-reject="{{ $req->id }}">{{ __('bloodcare.national.requests.reject') }}</button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-5">{{ __('bloodcare.national.requests.none') }}</td></tr>
            @endforelse
            </tbody>
        </table></div>

        @if($requests->total() > 0)
            <footer class="bc-lab-pagination">
                <span>{{ __('bloodcare.national.common.showing', ['from' => $requests->firstItem(), 'to' => $requests->lastItem(), 'total' => $requests->total()]) }}</span>
                <nav aria-label="{{ __('bloodcare.national.requests.pages') }}">
                    @if($requests->onFirstPage())<span class="bc-lab-page-arrow is-disabled" aria-hidden="true"><i class="la la-angle-left"></i></span>@else<a class="bc-lab-page-arrow" href="{{ $requests->previousPageUrl() }}" aria-label="{{ __('bloodcare.national.common.previous') }}"><i class="la la-angle-left"></i></a>@endif
                    @foreach($requests->getUrlRange(max(1, $requests->currentPage() - 2), min($requests->lastPage(), $requests->currentPage() + 2)) as $page => $url)<a href="{{ $url }}" class="{{ $page === $requests->currentPage() ? 'is-active' : '' }}" @if($page === $requests->currentPage()) aria-current="page" @endif>{{ $page }}</a>@endforeach
                    @if($requests->hasMorePages())<a class="bc-lab-page-arrow" href="{{ $requests->nextPageUrl() }}" aria-label="{{ __('bloodcare.national.common.next') }}"><i class="la la-angle-right"></i></a>@else<span class="bc-lab-page-arrow is-disabled" aria-hidden="true"><i class="la la-angle-right"></i></span>@endif
                </nav>
            </footer>
        @endif
    </section>
</div>

@foreach($requests as $req)
    <div class="bc-modal bc-request-detail-modal" id="bc-request-details-{{ $req->id }}" data-bc-request-modal="{{ $req->id }}" hidden>
        <button class="bc-modal-backdrop" type="button" data-modal-close aria-label="{{ __('bloodcare.national.requests.close') }}"></button>
        <section class="bc-modal-dialog bc-modal-dialog-wide bc-request-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="bc-request-details-title-{{ $req->id }}" tabindex="-1">
            <header class="bc-modal-header">
                <div>
                    <p class="bc-eyebrow">{{ $req->reference }}</p>
                    <h2 id="bc-request-details-title-{{ $req->id }}">{{ __('bloodcare.national.requests.details_title') }}</h2>
                    <p>{{ __('bloodcare.national.requests.details_help') }}</p>
                </div>
                <button class="bc-modal-close" type="button" data-modal-close aria-label="{{ __('bloodcare.national.requests.close') }}"><i class="la la-times"></i></button>
            </header>

            <div class="bc-request-detail-scroll">
                <dl class="bc-request-detail-grid">
                    <div><dt>{{ __('bloodcare.national.requests.request_code') }}</dt><dd>{{ $req->reference }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.common.status') }}</dt><dd><span class="bc-status bc-status-{{ $req->status }}">{{ __('bloodcare.national.common.'.$req->status) }}</span></dd></div>
                    <div><dt>{{ __('bloodcare.national.hospitals.hospital') }}</dt><dd>{{ $req->hospital?->name ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.requests.hospital_code') }}</dt><dd>{{ $req->hospital?->code ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.requests.hospital_phone') }}</dt><dd>{{ $req->hospital?->phone ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.requests.hospital_region') }}</dt><dd>{{ $req->hospital?->region ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                    <div class="bc-request-detail-wide"><dt>{{ __('bloodcare.national.requests.hospital_address') }}</dt><dd>{{ $req->hospital?->address ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.requests.portal_contact') }}</dt><dd>{{ $req->requester?->name ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.requests.contact_email') }}</dt><dd>{{ $req->requester?->email ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.requests.contact_phone') }}</dt><dd>{{ $req->requester?->phone ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.requests.patient_reference') }}</dt><dd>{{ $req->patient_reference }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.requests.priority') }}</dt><dd>{{ __('bloodcare.national.portal.'.$req->priority) }}</dd></div>
                    <div class="bc-request-detail-wide"><dt>{{ __('bloodcare.national.requests.need') }}</dt><dd>{{ $req->blood_group }} · {{ __('bloodcare.national.components.types.'.$req->component_type) }} · {{ __('bloodcare.national.requests.units', ['count' => $req->quantity]) }}</dd></div>
                    <div class="bc-request-detail-wide"><dt>{{ __('bloodcare.national.requests.special_requirements') }}</dt><dd>@php $requiredModifiers = collect(['leukoreduced','irradiated','washed'])->filter(fn ($modifier) => $req->{'requires_'.$modifier}); @endphp {{ $requiredModifiers->isEmpty() ? __('bloodcare.national.requests.no_special_requirements') : $requiredModifiers->map(fn ($modifier) => __('bloodcare.national.components.modifiers.'.$modifier))->join(' · ') }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.requests.submitted_at') }}</dt><dd>{{ $req->created_at?->format('Y-m-d H:i') ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                    <div><dt>{{ __('bloodcare.national.requests.reviewed_at') }}</dt><dd>{{ $req->reviewed_at?->format('Y-m-d H:i') ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                    <div class="bc-request-detail-wide"><dt>{{ __('bloodcare.national.requests.clinical_note') }}</dt><dd>{{ $req->clinical_note ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                    <div class="bc-request-detail-wide"><dt>{{ __('bloodcare.national.requests.decision_note') }}</dt><dd>{{ $req->decision_note ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                </dl>

                @if($req->status === 'pending')
                    <section class="bc-request-workflow-card">
                        <div class="bc-request-workflow-heading"><h3>{{ __('bloodcare.national.requests.review_request') }}</h3><p>{{ __('bloodcare.national.requests.review_help') }}</p></div>
                        <div class="bc-request-review-actions">
                            <form method="POST" action="{{ route('bloodcare.admin.blood-requests.review', $req) }}" class="bc-request-approve-form">@csrf @method('PATCH')<input type="hidden" name="decision" value="approve"><button class="btn btn-success" type="submit"><i class="la la-check" aria-hidden="true"></i> {{ __('bloodcare.national.requests.approve') }}</button></form>
                            <form method="POST" action="{{ route('bloodcare.admin.blood-requests.review', $req) }}" class="bc-request-reject-detail">@csrf @method('PATCH')<input type="hidden" name="decision" value="reject"><label><span>{{ __('bloodcare.national.requests.reject_reason') }}</span><textarea name="reason" rows="3" maxlength="2000" required data-bc-reject-reason placeholder="{{ __('bloodcare.national.requests.reject_reason_placeholder') }}"></textarea></label><button class="btn btn-outline-danger" type="submit"><i class="la la-times" aria-hidden="true"></i> {{ __('bloodcare.national.requests.reject') }}</button></form>
                        </div>
                    </section>
                @endif

                @if(in_array($req->status, ['approved','partially_allocated'], true))
                    @php
                        $eligibleUnits = $safeUnits
                            ->where('blood_group', $req->blood_group)
                            ->where('component_type', $req->component_type)
                            ->filter(fn ($unit) => (! $req->requires_leukoreduced || $unit->leukoreduced)
                                && (! $req->requires_irradiated || $unit->irradiated)
                                && (! $req->requires_washed || $unit->washed))
                            ->values();
                        $fefoUnit = $eligibleUnits->first();
                    @endphp
                    <section class="bc-request-workflow-card">
                        <div class="bc-request-workflow-heading">
                            <h3>{{ __('bloodcare.national.requests.allocations') }}</h3>
                            <p>{{ __('bloodcare.national.requests.fefo_help') }}</p>
                        </div>
                        @if($fefoUnit)
                            <div class="bc-fefo-recommendation" data-bc-fefo-unit="{{ $fefoUnit->unit_number }}">
                                <span class="bc-fefo-badge"><i class="la la-hourglass-half" aria-hidden="true"></i> {{ __('bloodcare.national.requests.fefo_recommended') }}</span>
                                <strong>{{ $fefoUnit->unit_number }}</strong>
                                <small>{{ __('bloodcare.national.requests.fefo_expires', ['date' => $fefoUnit->expires_at->format('Y-m-d')]) }} · {{ $fefoUnit->storage_location }}</small>
                            </div>
                        @else
                            <div class="bc-fefo-empty"><i class="la la-exclamation-triangle" aria-hidden="true"></i> {{ __('bloodcare.national.requests.no_safe_stock') }}</div>
                        @endif
                        <form method="POST" action="{{ route('bloodcare.admin.blood-requests.allocate', $req) }}" class="bc-allocation-form bc-request-allocation-form">@csrf
                            <div class="bc-allocation-field">
                                <label id="bc-unit-label-{{ $req->id }}" for="bc-unit-select-{{ $req->id }}">{{ __('bloodcare.national.requests.unit_field') }}</label>
                                <div class="bc-filter-dropdown bc-request-form-dropdown{{ $eligibleUnits->isEmpty() ? ' is-disabled' : '' }}" data-bc-national-select>
                                    <i class="la la-tint bc-filter-dropdown-icon" aria-hidden="true"></i>
                                    <select class="visually-hidden" id="bc-unit-select-{{ $req->id }}" name="unit_number" tabindex="-1" aria-labelledby="bc-unit-label-{{ $req->id }}" @disabled($eligibleUnits->isEmpty())>
                                        @if($eligibleUnits->isEmpty())
                                            <option value="" selected>{{ __('bloodcare.national.requests.no_safe_option') }}</option>
                                        @else
                                            @foreach($eligibleUnits as $unit)
                                                <option value="{{ $unit->unit_number }}" @selected($loop->first)>{{ $unit->unit_number }} · {{ $unit->expires_at->format('Y-m-d') }} · {{ $unit->storage_location }}{{ $loop->first ? ' · '.__('bloodcare.national.requests.fefo_recommended') : '' }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="bc-unit-menu-{{ $req->id }}" @disabled($eligibleUnits->isEmpty())>
                                        <span data-bc-select-label>{{ $fefoUnit ? $fefoUnit->unit_number.' · '.$fefoUnit->expires_at->format('Y-m-d').' · '.$fefoUnit->storage_location.' · '.__('bloodcare.national.requests.fefo_recommended') : __('bloodcare.national.requests.no_safe_option') }}</span>
                                        <i class="la la-angle-down" aria-hidden="true"></i>
                                    </button>
                                    <div class="bc-filter-dropdown-menu" id="bc-unit-menu-{{ $req->id }}" role="listbox" aria-labelledby="bc-unit-label-{{ $req->id }}" hidden>
                                        @foreach($eligibleUnits as $unit)
                                            <button type="button" role="option" data-value="{{ $unit->unit_number }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                                <span>{{ $unit->unit_number }} · {{ $unit->expires_at->format('Y-m-d') }} · {{ $unit->storage_location }}{{ $loop->first ? ' · '.__('bloodcare.national.requests.fefo_recommended') : '' }}</span>
                                                <i class="la la-check" aria-hidden="true"></i>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                                <small>{{ __('bloodcare.national.requests.unit_field_help') }}</small>
                            </div>

                            <div class="bc-allocation-field">
                                <label id="bc-crossmatch-label-{{ $req->id }}" for="bc-crossmatch-select-{{ $req->id }}">{{ __('bloodcare.national.requests.crossmatch_result') }}</label>
                                <div class="bc-filter-dropdown bc-request-form-dropdown" data-bc-national-select>
                                    <i class="la la-shield-alt bc-filter-dropdown-icon" aria-hidden="true"></i>
                                    <select class="visually-hidden" id="bc-crossmatch-select-{{ $req->id }}" name="crossmatch_result" tabindex="-1" aria-labelledby="bc-crossmatch-label-{{ $req->id }}">
                                        <option value="compatible" selected>{{ __('bloodcare.national.requests.compatible') }}</option>
                                        <option value="incompatible">{{ __('bloodcare.national.requests.incompatible') }}</option>
                                    </select>
                                    <button class="bc-filter-dropdown-trigger" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="bc-crossmatch-menu-{{ $req->id }}">
                                        <span data-bc-select-label>{{ __('bloodcare.national.requests.compatible') }}</span>
                                        <i class="la la-angle-down" aria-hidden="true"></i>
                                    </button>
                                    <div class="bc-filter-dropdown-menu" id="bc-crossmatch-menu-{{ $req->id }}" role="listbox" aria-labelledby="bc-crossmatch-label-{{ $req->id }}" hidden>
                                        <button type="button" role="option" data-value="compatible" aria-selected="true"><span>{{ __('bloodcare.national.requests.compatible') }}</span><i class="la la-check" aria-hidden="true"></i></button>
                                        <button type="button" role="option" data-value="incompatible" aria-selected="false"><span>{{ __('bloodcare.national.requests.incompatible') }}</span><i class="la la-check" aria-hidden="true"></i></button>
                                    </div>
                                </div>
                            </div>

                            <div class="bc-allocation-field">
                                <label for="bc-allocation-note-{{ $req->id }}">{{ __('bloodcare.national.requests.allocation_note') }}</label>
                                <input id="bc-allocation-note-{{ $req->id }}" name="notes" maxlength="2000" placeholder="{{ __('bloodcare.national.requests.allocation_note_placeholder') }}">
                            </div>

                            <button class="btn bc-btn-primary bc-request-allocate-button" type="submit" @disabled($eligibleUnits->isEmpty())><i class="la la-check-circle" aria-hidden="true"></i> {{ __('bloodcare.national.requests.allocate') }}</button>
                        </form>
                    </section>
                @endif

                <section class="bc-request-workflow-card">
                    <div class="bc-request-workflow-heading"><h3>{{ __('bloodcare.national.requests.allocations') }}</h3></div>
                    @forelse($req->allocations as $allocation)
                        <article class="bc-request-allocation-card">
                            <div><strong>{{ $allocation->unit?->unit_number ?: __('bloodcare.national.common.not_provided') }}</strong><span class="bc-status bc-status-{{ $allocation->status }}">{{ __('bloodcare.national.common.'.$allocation->status) }}</span></div>
                            <dl>
                                <div><dt>{{ __('bloodcare.national.requests.crossmatch_result') }}</dt><dd>{{ __('bloodcare.national.requests.'.$allocation->crossmatch_result) }}</dd></div>
                                <div><dt>{{ __('bloodcare.national.requests.allocated_at') }}</dt><dd>{{ $allocation->allocated_at?->format('Y-m-d H:i') ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                                <div><dt>{{ __('bloodcare.national.requests.dispatched_at') }}</dt><dd>{{ $allocation->dispatched_at?->format('Y-m-d H:i') ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                                <div class="bc-request-detail-wide"><dt>{{ __('bloodcare.national.requests.allocation_notes') }}</dt><dd>{{ $allocation->notes ?: __('bloodcare.national.common.not_provided') }}</dd></div>
                            </dl>
                            @if($allocation->status === 'allocated')
                                <form method="POST" action="{{ route('bloodcare.admin.allocations.dispatch', $allocation) }}" class="bc-request-dispatch-form">@csrf @method('PATCH')<input name="reason" required maxlength="1000" placeholder="{{ __('bloodcare.national.requests.dispatch_note') }}"><button class="btn bc-btn-primary btn-sm" type="submit">{{ __('bloodcare.national.requests.dispatch') }}</button></form>
                            @endif
                        </article>
                    @empty
                        <p class="bc-request-empty-allocation">{{ __('bloodcare.national.requests.no_allocations') }}</p>
                    @endforelse
                </section>
            </div>

            <footer class="bc-modal-footer"><button class="btn bc-btn-outline" type="button" data-modal-close>{{ __('bloodcare.national.requests.close') }}</button></footer>
        </section>
    </div>
@endforeach
@endsection
