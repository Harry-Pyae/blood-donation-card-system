@extends('security.two-factor-layout')

@section('title', __('bloodcare.two_factor.manage_title'))

@section('content')
<section class="bc-twofactor-hero">
    <div class="bc-twofactor-hero-icon"><i class="la la-shield-alt"></i></div>
    <div><p class="bc-eyebrow">{{ __('bloodcare.two_factor.eyebrow') }}</p><h1>{{ __('bloodcare.two_factor.manage_title') }}</h1><p>{{ __('bloodcare.two_factor.manage_subtitle') }}</p></div>
    <span class="bc-twofactor-status {{ $user->hasTwoFactorEnabled() ? 'is-enabled' : 'is-disabled' }}"><i class="la {{ $user->hasTwoFactorEnabled() ? 'la-check-circle' : 'la-exclamation-circle' }}"></i>{{ $user->hasTwoFactorEnabled() ? __('bloodcare.two_factor.enabled') : __('bloodcare.two_factor.disabled') }}</span>
</section>

<div class="bc-twofactor-grid">
    @if(!$user->hasTwoFactorEnabled())
        <section class="bc-twofactor-card bc-twofactor-card-primary">
            <div class="bc-twofactor-card-heading"><span><i class="la la-mobile"></i></span><div><h2>{{ __('bloodcare.two_factor.enable_title') }}</h2><p>{{ __('bloodcare.two_factor.enable_help') }}</p></div></div>
            <div class="bc-twofactor-steps">
                <div><b>1</b><span>{{ __('bloodcare.two_factor.step_password') }}</span></div>
                <div><b>2</b><span>{{ __('bloodcare.two_factor.step_scan') }}</span></div>
                <div><b>3</b><span>{{ __('bloodcare.two_factor.step_recovery') }}</span></div>
            </div>
            <form method="POST" action="{{ route('two-factor.setup.start') }}" class="bc-twofactor-form">@csrf
                <label for="two-factor-current-password">{{ __('bloodcare.two_factor.current_password') }}</label>
                <div class="bc-twofactor-input"><i class="la la-key"></i><input id="two-factor-current-password" name="current_password" type="password" required autocomplete="current-password" placeholder="{{ __('bloodcare.two_factor.password_placeholder') }}"></div>
                <button class="btn bc-btn-primary" type="submit"><i class="la la-qrcode"></i>{{ __('bloodcare.two_factor.begin_setup') }}</button>
            </form>
        </section>
    @else
        <section class="bc-twofactor-card bc-twofactor-card-primary">
            <div class="bc-twofactor-card-heading"><span class="is-success"><i class="la la-user-shield"></i></span><div><h2>{{ __('bloodcare.two_factor.protected_title') }}</h2><p>{{ __('bloodcare.two_factor.protected_help') }}</p></div></div>
            <dl class="bc-twofactor-summary">
                <div><dt>{{ __('bloodcare.two_factor.authenticator') }}</dt><dd>{{ __('bloodcare.two_factor.active') }}</dd></div>
                <div><dt>{{ __('bloodcare.two_factor.enabled_at') }}</dt><dd>{{ $user->two_factor_confirmed_at?->locale(app()->getLocale())->translatedFormat('d M Y, H:i') }}</dd></div>
                <div><dt>{{ __('bloodcare.two_factor.recovery_remaining') }}</dt><dd>{{ count($user->two_factor_recovery_codes ?? []) }}</dd></div>
            </dl>
        </section>

        <section class="bc-twofactor-card">
            <div class="bc-twofactor-card-heading"><span><i class="la la-life-ring"></i></span><div><h2>{{ __('bloodcare.two_factor.regenerate_title') }}</h2><p>{{ __('bloodcare.two_factor.regenerate_help') }}</p></div></div>
            <form method="POST" action="{{ route('two-factor.recovery-codes.regenerate') }}" class="bc-twofactor-form">@csrf
                <label>{{ __('bloodcare.two_factor.current_password') }}<input name="current_password" type="password" required autocomplete="current-password"></label>
                <label>{{ __('bloodcare.two_factor.code_or_recovery') }}<input name="code" type="text" required maxlength="32" autocomplete="one-time-code"></label>
                <button class="btn bc-btn-outline" type="submit"><i class="la la-sync"></i>{{ __('bloodcare.two_factor.regenerate_button') }}</button>
            </form>
        </section>

        <section class="bc-twofactor-card bc-twofactor-danger-card">
            <div class="bc-twofactor-card-heading"><span><i class="la la-unlock"></i></span><div><h2>{{ __('bloodcare.two_factor.disable_title') }}</h2><p>{{ __('bloodcare.two_factor.disable_help') }}</p></div></div>
            <form method="POST" action="{{ route('two-factor.disable') }}" class="bc-twofactor-form">@csrf @method('DELETE')
                <label>{{ __('bloodcare.two_factor.current_password') }}<input name="current_password" type="password" required autocomplete="current-password"></label>
                <label>{{ __('bloodcare.two_factor.code_or_recovery') }}<input name="code" type="text" required maxlength="32" autocomplete="one-time-code"></label>
                <button class="btn bc-twofactor-danger-button" type="submit"><i class="la la-times-circle"></i>{{ __('bloodcare.two_factor.disable_button') }}</button>
            </form>
        </section>
    @endif

    <aside class="bc-twofactor-card bc-twofactor-info-card">
        <div class="bc-twofactor-card-heading"><span><i class="la la-info-circle"></i></span><div><h2>{{ __('bloodcare.two_factor.how_title') }}</h2><p>{{ __('bloodcare.two_factor.how_help') }}</p></div></div>
        <ul class="bc-twofactor-info-grid">
            <li><i class="la la-mobile"></i><span>{{ __('bloodcare.two_factor.compatible_apps') }}</span></li>
            <li><i class="la la-key"></i><span>{{ __('bloodcare.two_factor.offline_codes') }}</span></li>
            <li><i class="la la-wifi"></i><span>{{ __('bloodcare.two_factor.no_sms') }}</span></li>
        </ul>
    </aside>
</div>
@endsection
