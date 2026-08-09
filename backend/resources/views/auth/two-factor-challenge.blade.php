@extends(backpack_view('layouts.auth'))

@section('title', __('bloodcare.two_factor.challenge_title'))

@push('before_styles')
    @include('admin.partials.favicon')
@endpush
@push('after_styles')
    <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}?v={{ filemtime(public_path('css/bloodcare-admin.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/bloodcare-two-factor.css') }}?v={{ filemtime(public_path('css/bloodcare-two-factor.css')) }}">
@endpush

@section('content')
<main class="bc-login-page bc-twofactor-challenge-page">
    <div class="bc-login-shell">
        <section class="bc-login-brand-panel" aria-label="BloodCare">
            <a class="bc-login-brand" href="{{ route('home') }}"><span class="bc-login-brand-mark">+</span><span><strong>Blood</strong>Care</span></a>
            <div class="bc-login-brand-copy"><span class="bc-login-drop"><i class="la la-shield-alt"></i></span><p class="bc-login-eyebrow">{{ __('bloodcare.two_factor.eyebrow') }}</p><h1>{{ __('bloodcare.two_factor.challenge_brand_title') }}</h1><p>{{ __('bloodcare.two_factor.challenge_brand_help') }}</p><ul class="bc-login-features"><li><i class="la la-check"></i><span>{{ __('bloodcare.two_factor.challenge_feature_authenticator') }}</span></li><li><i class="la la-check"></i><span>{{ __('bloodcare.two_factor.challenge_feature_recovery') }}</span></li></ul></div>
            <p class="bc-login-brand-footer">BloodCare Management System</p>
        </section>
        <section class="bc-login-form-panel">
            <div class="bc-login-toolbar">@include('admin.partials.utility-controls')</div>
            <div class="bc-login-card bc-twofactor-challenge-card">
                <span class="bc-twofactor-challenge-icon"><i class="la la-mobile"></i></span>
                <p class="bc-login-eyebrow">{{ $context === 'hospital' ? __('bloodcare.two_factor.hospital_access') : __('bloodcare.two_factor.staff_access') }}</p>
                <h2>{{ __('bloodcare.two_factor.challenge_title') }}</h2>
                <p class="bc-login-subtitle">{{ __('bloodcare.two_factor.challenge_subtitle', ['email' => $email]) }}</p>
                <form method="POST" action="{{ route('two-factor.challenge.verify') }}">@csrf
                    <div class="bc-login-field"><label for="two-factor-challenge-code">{{ __('bloodcare.two_factor.code_or_recovery') }}</label><div class="bc-login-input"><i class="la la-key"></i><input id="two-factor-challenge-code" name="code" type="text" required maxlength="32" autocomplete="one-time-code" autofocus placeholder="{{ __('bloodcare.two_factor.challenge_placeholder') }}" class="form-control {{ $errors->has('code') ? 'is-invalid' : '' }}"></div>@error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                    <button class="btn bc-login-submit" type="submit"><span>{{ __('bloodcare.two_factor.verify_continue') }}</span><i class="la la-arrow-right"></i></button>
                </form>
                <form method="POST" action="{{ route('two-factor.challenge.cancel') }}" class="bc-twofactor-cancel-form">@csrf<button type="submit">{{ __('bloodcare.two_factor.cancel_login') }}</button></form>
                <div class="bc-login-security"><i class="la la-lock"></i><p><strong>{{ __('bloodcare.two_factor.recovery_hint_title') }}</strong><span>{{ __('bloodcare.two_factor.recovery_hint') }}</span></p></div>
            </div>
        </section>
    </div>
</main>
@endsection

@push('after_scripts')
<script src="{{ asset('js/bloodcare-auth.js') }}?v={{ filemtime(public_path('js/bloodcare-auth.js')) }}" defer></script>
@endpush
