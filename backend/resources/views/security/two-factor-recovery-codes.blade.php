@extends('security.two-factor-layout')

@section('title', __('bloodcare.two_factor.recovery_title'))

@push('scripts')
<script src="{{ asset('js/bloodcare-two-factor.js') }}?v={{ filemtime(public_path('js/bloodcare-two-factor.js')) }}" defer></script>
@endpush

@section('content')
<section class="bc-twofactor-hero bc-twofactor-hero-compact">
    <div class="bc-twofactor-hero-icon is-success"><i class="la la-check"></i></div>
    <div><p class="bc-eyebrow">{{ __('bloodcare.two_factor.enabled') }}</p><h1>{{ __('bloodcare.two_factor.recovery_title') }}</h1><p>{{ __('bloodcare.two_factor.recovery_subtitle') }}</p></div>
</section>

<section class="bc-twofactor-card bc-twofactor-recovery-card">
    <div class="bc-twofactor-warning"><i class="la la-exclamation-triangle"></i><div><strong>{{ __('bloodcare.two_factor.save_now') }}</strong><p>{{ __('bloodcare.two_factor.save_now_help') }}</p></div></div>
    <div class="bc-twofactor-recovery-grid" data-bc-recovery-codes>@foreach($codes as $code)<code>{{ $code }}</code>@endforeach</div>
    <div class="bc-twofactor-recovery-actions">
        <button class="btn bc-btn-outline" type="button" data-bc-copy-recovery data-copy-success="{{ __('bloodcare.two_factor.copied') }}"><i class="la la-copy"></i><span>{{ __('bloodcare.two_factor.copy_codes') }}</span></button>
        <a class="btn bc-btn-primary" href="{{ route('two-factor.manage') }}"><i class="la la-shield-alt"></i>{{ __('bloodcare.two_factor.done') }}</a>
    </div>
</section>
@endsection
