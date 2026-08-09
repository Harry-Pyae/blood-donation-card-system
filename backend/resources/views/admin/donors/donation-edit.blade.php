@extends(backpack_view('blank'))

@section('title', __('bloodcare.donor_details.edit_donation'))

@push('before_styles')
    @include('admin.partials.favicon')
@endpush

@push('after_styles')
    <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}?v={{ filemtime(public_path('css/bloodcare-admin.css')) }}">
@endpush

@section('content')
    <div class="bc-admin-page bc-donor-record-page">
        <header class="bc-page-heading bc-record-heading">
            <div class="bc-module-title">
                <span class="bc-module-icon"><i class="la la-tint"></i></span>
                <div>
                    <p class="bc-eyebrow">{{ $donation->reference }}</p>
                    <h1>{{ __('bloodcare.donor_details.edit_donation') }}</h1>
                    <p>{{ $donor->full_name }} · {{ __('bloodcare.donor_details.edit_donation_help') }}</p>
                </div>
            </div>
            <div class="bc-heading-tools">
                @include('admin.partials.utility-controls')
                <a class="btn bc-btn-outline" href="{{ route('bloodcare.admin.donors.show', $donor) }}#donation-history"><i class="la la-arrow-left"></i> {{ __('bloodcare.donor_details.back_to_history') }}</a>
            </div>
        </header>

        @if ($errors->any())
            <div class="bc-form-alert-error bc-record-alert" role="alert" tabindex="-1"><strong>{{ __('bloodcare.donor_details.fix_errors') }}</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form class="bc-panel bc-record-section bc-history-edit-form" method="post" action="{{ route('bloodcare.admin.donations.update', $donation->reference) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="donorId" value="{{ $donor->reference }}">
            <input type="hidden" name="group" value="{{ $donor->blood_group }}">
            <input type="hidden" name="appointmentReference" value="{{ $donation->appointment?->reference }}">
            <input type="hidden" name="screeningReference" value="{{ $donation->screening?->reference }}">

            <header class="bc-record-section-heading">
                <span><i class="la la-pen"></i></span>
                <div><h2>{{ __('bloodcare.donor_details.donation_record_details') }}</h2><p>{{ __('bloodcare.donor_details.edit_history_warning') }}</p></div>
            </header>

            <div class="bc-form-grid bc-form-grid-three">
                <label class="bc-field"><span>{{ __('bloodcare.donor_details.donation_date') }}</span><input class="form-control" name="donationDate" type="date" max="{{ now()->toDateString() }}" value="{{ old('donationDate', $donation->donation_date?->toDateString()) }}" required></label>
                <label class="bc-field"><span>{{ __('bloodcare.donor_details.donation_type') }}</span><select class="form-select" name="donationType" required>@foreach (['whole_blood', 'platelets', 'plasma'] as $value)<option value="{{ $value }}" @selected(old('donationType', $donation->donation_type) === $value)>{{ __('bloodcare.donor_details.'.$value) }}</option>@endforeach</select></label>
                <label class="bc-field"><span>{{ __('bloodcare.donor_details.quantity_ml') }}</span><input class="form-control" name="quantity" type="number" min="100" max="600" value="{{ old('quantity', $donation->quantity_ml) }}" required></label>
                <label class="bc-field"><span>{{ __('bloodcare.donor_details.screening_result') }}</span><select class="form-select" name="screeningResult" required>@foreach (['Passed', 'Pending', 'Failed'] as $value)<option value="{{ $value }}" @selected(old('screeningResult', ucfirst($donation->screening_result)) === $value)>{{ __('bloodcare.donations.'.strtolower($value)) }}</option>@endforeach</select></label>
                <label class="bc-field"><span>{{ __('bloodcare.donors.status') }}</span><select class="form-select" name="status" required>@foreach (['Accepted', 'Screening', 'Rejected'] as $value)<option value="{{ $value }}" @selected(old('status', ucfirst($donation->status)) === $value)>{{ __('bloodcare.donations.'.strtolower($value)) }}</option>@endforeach</select></label>
                <label class="bc-field"><span>{{ __('bloodcare.donor_details.bag_unit') }}</span><input class="form-control" name="bagUnit" value="{{ old('bagUnit', $donation->bag_unit_number) }}" maxlength="40" required></label>
                <label class="bc-field"><span>{{ __('bloodcare.donor_details.expires_at') }}</span><input class="form-control" name="expiryDate" type="date" value="{{ old('expiryDate', $donation->expires_at?->toDateString()) }}" required></label>
                <label class="bc-field"><span>{{ __('bloodcare.donor_details.storage_location') }}</span><input class="form-control" name="location" value="{{ old('location', $donation->storage_location) }}" maxlength="120" required></label>
                <label class="bc-field"><span>{{ __('bloodcare.donor_details.staff') }}</span><input class="form-control" value="{{ backpack_user()?->name }}" readonly></label>
                <label class="bc-field"><span>{{ __('bloodcare.donor_details.appointment') }}</span><input class="form-control" value="{{ $donation->appointment?->reference ?? __('bloodcare.donor_details.no_value') }}" readonly></label>
                <label class="bc-field"><span>{{ __('bloodcare.donor_details.linked_screening') }}</span><input class="form-control" value="{{ $donation->screening?->reference ?? __('bloodcare.donor_details.no_value') }}" readonly></label>
                <label class="bc-field"><span>{{ __('bloodcare.donors.blood_group') }}</span><input class="form-control" value="{{ $donor->blood_group }}" readonly></label>
            </div>
            <label class="bc-field"><span>{{ __('bloodcare.donor_details.screening_notes') }}</span><textarea class="form-control" name="notes" rows="4" maxlength="2000">{{ old('notes', $donation->screening_notes) }}</textarea></label>

            <footer class="bc-record-actions">
                <a class="btn bc-btn-outline" href="{{ route('bloodcare.admin.donors.show', $donor) }}#donation-history">{{ __('bloodcare.donor_details.cancel_edit') }}</a>
                <button class="btn bc-btn-primary" type="submit"><i class="la la-save"></i> {{ __('bloodcare.donor_details.save_correction') }}</button>
            </footer>
        </form>
    </div>
@endsection
