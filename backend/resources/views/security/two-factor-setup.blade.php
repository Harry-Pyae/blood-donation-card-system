@extends('security.two-factor-layout')

@section('title', __('bloodcare.two_factor.setup_title'))

@push('scripts')
<script src="{{ asset('vendor/bloodcare-qrcode/qrcode.js') }}?v={{ filemtime(public_path('vendor/bloodcare-qrcode/qrcode.js')) }}" defer></script>
<script src="{{ asset('js/bloodcare-two-factor.js') }}?v={{ filemtime(public_path('js/bloodcare-two-factor.js')) }}" defer></script>
@endpush

@section('content')
<section class="bc-twofactor-hero bc-twofactor-hero-compact">
    <div class="bc-twofactor-hero-icon"><i class="la la-qrcode"></i></div>
    <div><p class="bc-eyebrow">{{ __('bloodcare.two_factor.eyebrow') }}</p><h1>{{ __('bloodcare.two_factor.setup_title') }}</h1><p>{{ __('bloodcare.two_factor.setup_subtitle') }}</p></div>
</section>

<div class="bc-twofactor-setup-grid">
    <section class="bc-twofactor-card bc-twofactor-qr-card">
        <div class="bc-twofactor-step-badge">1</div><h2>{{ __('bloodcare.two_factor.scan_title') }}</h2><p>{{ __('bloodcare.two_factor.scan_help') }}</p>
        <div class="bc-twofactor-qr" data-bc-two-factor-qr="{{ $provisioningUri }}" aria-label="{{ __('bloodcare.two_factor.scan_title') }}"></div>
        <p class="bc-twofactor-manual-label">{{ __('bloodcare.two_factor.manual_key') }}</p>
        <code class="bc-twofactor-secret">{{ implode(' ', str_split($secret, 4)) }}</code>
    </section>

    <section class="bc-twofactor-card bc-twofactor-confirm-card">
        <div class="bc-twofactor-step-badge">2</div><h2>{{ __('bloodcare.two_factor.confirm_title') }}</h2><p>{{ __('bloodcare.two_factor.confirm_help') }}</p>
        <form method="POST" action="{{ route('two-factor.confirm') }}" class="bc-twofactor-form">@csrf
            <label for="two-factor-code">{{ __('bloodcare.two_factor.six_digit_code') }}</label>
            <input id="two-factor-code" class="bc-twofactor-code-input" name="code" type="text" required inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="000000" autofocus>
            @error('code')<p class="bc-twofactor-field-error">{{ $message }}</p>@enderror
            <button class="btn bc-btn-primary" type="submit"><i class="la la-check-circle"></i>{{ __('bloodcare.two_factor.confirm_enable') }}</button>
        </form>
        <div class="bc-twofactor-note"><i class="la la-clock"></i><p>{{ __('bloodcare.two_factor.time_note') }}</p></div>
    </section>
</div>
@endsection
