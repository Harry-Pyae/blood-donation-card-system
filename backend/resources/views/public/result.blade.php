@extends('layouts.public')

@section('title', $title)

@section('content')
    <section class="result-section">
        <div class="public-container result-wrap">
            <div class="result-card" @isset($lookupType) data-public-lookup-result data-lookup-type="{{ $lookupType }}" @endisset>
                <div class="result-heading">
                    <div class="result-symbol"><x-bloodcare-icon name="check" :size="35" /></div>
                    <div>
                        <p class="eyebrow">{{ $eyebrow }}</p>
                        <h1>{{ $title }}</h1>
                    </div>
                </div>
                <p class="result-message">{{ $message }}</p>

                @isset($card)
                    <article class="digital-card" aria-label="{{ __('bloodcare.public.result.digital_card_aria') }}">
                        <div class="digital-card-head">
                            <span>{{ __('bloodcare.public.result.bloodcare_donor') }}</span>
                            <span>{{ $card['status'] }}</span>
                        </div>
                        <div class="digital-card-body">
                            <div>
                                <span>{{ __('bloodcare.public.result.donor_name') }}</span>
                                <strong>{{ $card['name'] }}</strong>
                            </div>
                            <strong class="digital-card-blood">{{ $card['blood_group'] }}</strong>
                        </div>
                        <div class="digital-card-meta">
                            <div><span>{{ __('bloodcare.public.result.card_number') }}</span><strong>{{ $reference }}</strong></div>
                            <div><span>{{ __('bloodcare.public.result.last_donation') }}</span><strong>{{ $card['last_donation'] }}</strong></div>
                            <div><span>{{ __('bloodcare.public.result.next_eligible') }}</span><strong>{{ $card['next_eligible'] }}</strong></div>
                        </div>
                    </article>
                @else
                    <div class="reference-box">
                        <div>
                            <span>{{ __('bloodcare.public.result.reference') }}</span>
                            <strong>{{ $reference }}</strong>
                        </div>
                        <x-bloodcare-icon name="card" :size="30" />
                    </div>
                @endisset

                @if (count($details))
                    <div class="result-details">
                        @foreach ($details as $label => $value)
                            <div class="result-detail">
                                <span>{{ $label }}</span>
                                <strong>{{ $value }}</strong>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="prototype-notice">
                    <x-bloodcare-icon name="info" :size="23" />
                    <span>{{ $notice }}</span>
                </div>

                <div class="result-safety-note">
                    <x-bloodcare-icon name="shield" :size="24" />
                    <div>
                        <strong>{{ __('bloodcare.public.result.privacy_title') }}</strong>
                        <p>{{ __('bloodcare.public.result.privacy_text') }}</p>
                    </div>
                </div>

                <div class="result-actions">
                    <a class="button button-primary" href="{{ $primaryUrl }}">{{ $primaryLabel }}</a>
                    <a class="button button-secondary" href="{{ $secondaryUrl }}">{{ $secondaryLabel }}</a>
                </div>
            </div>
        </div>
    </section>
@endsection
