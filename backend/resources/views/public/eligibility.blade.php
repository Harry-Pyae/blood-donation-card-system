@extends('layouts.public')

@section('title', __('bloodcare.public.eligibility.title'))
@section('meta_description', __('bloodcare.public.eligibility.meta'))

@section('content')
    <section class="page-hero">
        <div class="public-container page-hero-grid">
            <div>
                <p class="eyebrow">{{ __('bloodcare.public.eligibility.eyebrow') }}</p>
                <h1>{{ __('bloodcare.public.eligibility.hero_title') }}</h1>
                <p>{{ __('bloodcare.public.eligibility.hero_text') }}</p>
            </div>
            <div class="page-hero-icon"><x-bloodcare-icon name="shield" :size="62" /></div>
        </div>
    </section>

    <section class="section section-soft">
        <div class="public-container">
            <div class="section-heading">
                <p class="eyebrow">{{ __('bloodcare.public.eligibility.basic_eyebrow') }}</p>
                <h2>{{ __('bloodcare.public.eligibility.basic_title') }}</h2>
                <p>{{ __('bloodcare.public.eligibility.basic_text') }}</p>
            </div>

            <div class="content-grid">
                <article class="content-card" data-reveal>
                    <span class="content-card-icon"><x-bloodcare-icon name="user" :size="29" /></span>
                    <h2>{{ __('bloodcare.public.eligibility.age_title') }}</h2>
                    <p>{{ __('bloodcare.public.eligibility.age_text') }}</p>
                    <ul class="check-list">
                        <li><span class="list-icon"><x-bloodcare-icon name="check" :size="18" /></span> {{ __('bloodcare.public.eligibility.age_item_one') }}</li>
                        <li><span class="list-icon"><x-bloodcare-icon name="check" :size="18" /></span> {{ __('bloodcare.public.eligibility.age_item_two') }}</li>
                    </ul>
                </article>

                <article class="content-card" data-reveal>
                    <span class="content-card-icon"><x-bloodcare-icon name="heart" :size="29" /></span>
                    <h2>{{ __('bloodcare.public.eligibility.wellbeing_title') }}</h2>
                    <p>{{ __('bloodcare.public.eligibility.wellbeing_text') }}</p>
                    <ul class="check-list">
                        <li><span class="list-icon"><x-bloodcare-icon name="check" :size="18" /></span> {{ __('bloodcare.public.eligibility.wellbeing_item_one') }}</li>
                        <li><span class="list-icon"><x-bloodcare-icon name="check" :size="18" /></span> {{ __('bloodcare.public.eligibility.wellbeing_item_two') }}</li>
                    </ul>
                </article>

                <article class="content-card" data-reveal>
                    <span class="content-card-icon"><x-bloodcare-icon name="clock" :size="29" /></span>
                    <h2>{{ __('bloodcare.public.eligibility.time_title') }}</h2>
                    <p>{{ __('bloodcare.public.eligibility.time_text') }}</p>
                    <ul class="check-list">
                        <li><span class="list-icon"><x-bloodcare-icon name="check" :size="18" /></span> {{ __('bloodcare.public.eligibility.time_item_one') }}</li>
                        <li><span class="list-icon"><x-bloodcare-icon name="check" :size="18" /></span> {{ __('bloodcare.public.eligibility.time_item_two') }}</li>
                    </ul>
                </article>

                <article class="content-card" data-reveal>
                    <span class="content-card-icon"><x-bloodcare-icon name="shield" :size="29" /></span>
                    <h2>{{ __('bloodcare.public.eligibility.deferral_title') }}</h2>
                    <p>{{ __('bloodcare.public.eligibility.deferral_text') }}</p>
                    <ul class="check-list">
                        <li><span class="list-icon"><x-bloodcare-icon name="check" :size="18" /></span> {{ __('bloodcare.public.eligibility.deferral_item_one') }}</li>
                        <li><span class="list-icon"><x-bloodcare-icon name="check" :size="18" /></span> {{ __('bloodcare.public.eligibility.deferral_item_two') }}</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    <section class="section eligibility-check-section">
        <div class="public-container eligibility-check-layout">
            <div class="eligibility-check-copy">
                <p class="eyebrow">{{ __('bloodcare.public.eligibility.check_eyebrow') }}</p>
                <h2>{{ __('bloodcare.public.eligibility.check_title') }}</h2>
                <p>{{ __('bloodcare.public.eligibility.check_text') }}</p>

                <div class="eligibility-safety-note">
                    <x-bloodcare-icon name="info" :size="25" />
                    <p>{{ __('bloodcare.public.eligibility.check_notice') }}</p>
                </div>
            </div>

            <form class="eligibility-checker" data-eligibility-checker>
                @foreach ([
                    'unwell',
                    'medication',
                    'procedure',
                    'travel',
                    'recent_donation',
                ] as $question)
                    <fieldset class="eligibility-question" data-eligibility-question>
                        <legend>{{ __('bloodcare.public.eligibility.questions.'.$question) }}</legend>
                        <div class="eligibility-answers">
                            <label>
                                <input type="radio" name="{{ $question }}" value="yes">
                                <span>{{ __('bloodcare.public.eligibility.yes') }}</span>
                            </label>
                            <label>
                                <input type="radio" name="{{ $question }}" value="no">
                                <span>{{ __('bloodcare.public.eligibility.no') }}</span>
                            </label>
                        </div>
                    </fieldset>
                @endforeach

                <div class="eligibility-check-actions">
                    <button class="button button-primary" type="submit">
                        <x-bloodcare-icon name="check" :size="22" />
                        {{ __('bloodcare.public.eligibility.show_guidance') }}
                    </button>
                    <button class="button button-secondary" type="reset">
                        {{ __('bloodcare.public.eligibility.reset') }}
                    </button>
                </div>

                <div class="eligibility-guidance" role="status" aria-live="polite" tabindex="-1" data-eligibility-result hidden>
                    <span class="eligibility-guidance-icon"><x-bloodcare-icon name="info" :size="28" /></span>
                    <div>
                        <h3 data-eligibility-result-title></h3>
                        <p data-eligibility-result-text></p>
                        <a href="{{ route('appointments.book') }}" data-eligibility-result-link>
                            {{ __('bloodcare.public.eligibility.book') }}
                            <x-bloodcare-icon name="arrow" :size="20" />
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <section class="section section-soft">
        <div class="public-container">
            <article class="content-card final-screening-card" data-reveal>
                <span class="content-card-icon"><x-bloodcare-icon name="info" :size="29" /></span>
                <div>
                    <h2>{{ __('bloodcare.public.eligibility.final_title') }}</h2>
                    <p>{{ __('bloodcare.public.eligibility.final_text') }}</p>
                    <div class="self-check">
                        <div>
                            <h3>{{ __('bloodcare.public.eligibility.unsure_title') }}</h3>
                            <p>{{ __('bloodcare.public.eligibility.unsure_text') }}</p>
                        </div>
                        <a class="button button-primary" href="{{ route('appointments.book') }}">{{ __('bloodcare.public.eligibility.book') }}</a>
                    </div>
                </div>
            </article>
        </div>
    </section>

    @php
        $eligibilityConfig = [
            'labels' => [
                'answerAllTitle' => __('bloodcare.public.eligibility.answer_all_title'),
                'answerAllText' => __('bloodcare.public.eligibility.answer_all_text'),
                'readyTitle' => __('bloodcare.public.eligibility.ready_title'),
                'readyText' => __('bloodcare.public.eligibility.ready_text'),
                'reviewTitle' => __('bloodcare.public.eligibility.review_title'),
                'reviewText' => __('bloodcare.public.eligibility.review_text'),
            ],
        ];
    @endphp
    <script id="bc-public-eligibility-config" type="application/json">{!! json_encode($eligibilityConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endsection
