@extends(backpack_view('blank'))

@section('title', __('bloodcare.donor_details.edit_screening'))

@push('before_styles')
    @include('admin.partials.favicon')
@endpush

@push('after_styles')
    <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}?v={{ filemtime(public_path('css/bloodcare-admin.css')) }}">
@endpush

@push('after_scripts')
    <script src="{{ asset('js/bloodcare-donor-form.js') }}?v={{ filemtime(public_path('js/bloodcare-donor-form.js')) }}" defer></script>
@endpush

@section('content')
    <div class="bc-admin-page bc-donor-record-page" data-bc-donor-details>
        <header class="bc-page-heading bc-record-heading">
            <div class="bc-module-title">
                <span class="bc-module-icon"><i class="la la-notes-medical"></i></span>
                <div>
                    <p class="bc-eyebrow">{{ $screening->reference }}</p>
                    <h1>{{ __('bloodcare.donor_details.edit_screening') }}</h1>
                    <p>{{ $donor->full_name }} · {{ __('bloodcare.donor_details.edit_screening_help') }}</p>
                </div>
            </div>
            <div class="bc-heading-tools">
                @include('admin.partials.utility-controls')
                <a class="btn bc-btn-outline" href="{{ route('bloodcare.admin.donors.show', $donor) }}#screening-history"><i class="la la-arrow-left"></i> {{ __('bloodcare.donor_details.back_to_history') }}</a>
            </div>
        </header>

        @if ($errors->any())
            <div class="bc-form-alert-error bc-record-alert" role="alert" tabindex="-1"><strong>{{ __('bloodcare.donor_details.fix_errors') }}</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form class="bc-panel bc-record-section bc-history-edit-form" method="post" action="{{ route('bloodcare.admin.donors.screenings.update', [$donor, $screening]) }}">
            @csrf
            @method('PUT')
            <header class="bc-record-section-heading">
                <span><i class="la la-pen"></i></span>
                <div><h2>{{ __('bloodcare.donor_details.screening_record_details') }}</h2><p>{{ __('bloodcare.donor_details.edit_history_warning') }}</p></div>
            </header>
            @include('admin.donors.screening-fields', ['prefix' => 'edit'])
            <footer class="bc-record-actions">
                <a class="btn bc-btn-outline" href="{{ route('bloodcare.admin.donors.show', $donor) }}#screening-history">{{ __('bloodcare.donor_details.cancel_edit') }}</a>
                <button class="btn bc-btn-primary" type="submit"><i class="la la-save"></i> {{ __('bloodcare.donor_details.save_correction') }}</button>
            </footer>
        </form>
    </div>
@endsection
