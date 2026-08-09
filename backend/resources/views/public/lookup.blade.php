@extends('layouts.public')

@section('title', $type === 'card' ? __('bloodcare.public.lookup.card_title') : __('bloodcare.public.lookup.appointment_title'))
@section('meta_description', $description)

@section('content')
    <section class="lookup-section">
        <div class="public-container lookup-grid">
            <div class="lookup-copy">
                <p class="eyebrow">{{ $eyebrow }}</p>
                <h1>{{ $title }}</h1>
                <p>{{ $description }}</p>

                <nav class="lookup-switcher" aria-label="{{ __('bloodcare.public.lookup.switch_aria') }}">
                    <a href="{{ route('card.check') }}" class="{{ $type === 'card' ? 'active' : '' }}" @if($type === 'card') aria-current="page" @endif>
                        <x-bloodcare-icon name="card" :size="23" />
                        {{ __('bloodcare.public.lookup.card_tab') }}
                    </a>
                    <a href="{{ route('appointments.check') }}" class="{{ $type === 'appointment' ? 'active' : '' }}" @if($type === 'appointment') aria-current="page" @endif>
                        <x-bloodcare-icon name="calendar" :size="23" />
                        {{ __('bloodcare.public.lookup.appointment_tab') }}
                    </a>
                </nav>

                <div class="privacy-note">
                    <x-bloodcare-icon name="shield" :size="25" />
                    <span>{{ __('bloodcare.public.lookup.privacy') }}</span>
                </div>

                <ul class="lookup-help-list" aria-label="{{ __('bloodcare.public.lookup.help_aria') }}">
                    <li><x-bloodcare-icon name="check" :size="20" /> {{ __('bloodcare.public.lookup.help_reference') }}</li>
                    <li><x-bloodcare-icon name="check" :size="20" /> {{ __('bloodcare.public.lookup.help_phone') }}</li>
                    <li><x-bloodcare-icon name="check" :size="20" /> {{ __('bloodcare.public.lookup.help_safe') }}</li>
                </ul>
            </div>

            <div class="lookup-card">
                <div class="lookup-card-heading">
                    <div class="lookup-card-icon">
                        <x-bloodcare-icon :name="$type === 'card' ? 'card' : 'calendar'" :size="30" />
                    </div>
                    <div>
                        <h2>{{ $type === 'card' ? __('bloodcare.public.lookup.card_form_title') : __('bloodcare.public.lookup.appointment_form_title') }}</h2>
                        <p>{{ __('bloodcare.public.lookup.form_help') }}</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="error-summary" role="alert" tabindex="-1" data-lookup-error-summary>
                        <strong>{{ __('bloodcare.public.lookup.error_title') }}</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ $action }}"
                    data-public-lookup-form
                    data-submitting-label="{{ __('bloodcare.public.lookup.searching') }}"
                >
                    @csrf
                    <div class="form-field">
                        <label for="reference">{{ $referenceLabel }} <span class="required">*</span></label>
                        <input
                            id="reference"
                            name="reference"
                            value="{{ old('reference') }}"
                            placeholder="{{ $referencePlaceholder }}"
                            maxlength="40"
                            autocomplete="off"
                            spellcheck="false"
                            required
                        >
                        <small>{{ $type === 'card' ? __('bloodcare.public.lookup.card_reference_help') : __('bloodcare.public.lookup.appointment_reference_help') }}</small>
                    </div>
                    <div class="form-field">
                        <label for="phone">{{ __('bloodcare.public.lookup.phone') }} <span class="required">*</span></label>
                        <input
                            id="phone"
                            name="phone"
                            value="{{ old('phone') }}"
                            placeholder="{{ __('bloodcare.public.lookup.phone_placeholder') }}"
                            maxlength="30"
                            inputmode="tel"
                            autocomplete="tel"
                            required
                        >
                        <small>{{ __('bloodcare.public.lookup.phone_help') }}</small>
                    </div>
                    <button class="button button-primary" type="submit" data-lookup-submit>
                        <x-bloodcare-icon name="search" :size="22" />
                        <span data-lookup-submit-label>{{ $type === 'card' ? __('bloodcare.public.lookup.find_card') : __('bloodcare.public.lookup.find_appointment') }}</span>
                    </button>
                </form>

                <p class="lookup-support">
                    <x-bloodcare-icon name="info" :size="21" />
                    <span>{{ __('bloodcare.public.lookup.support') }}</span>
                </p>
            </div>
        </div>
    </section>
@endsection
