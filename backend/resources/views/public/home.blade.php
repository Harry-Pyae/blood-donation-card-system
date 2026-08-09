@extends('layouts.public')

@section('title', __('bloodcare.public.home.title'))
@section('meta_description', __('bloodcare.public.home.meta'))

@section('content')
    <section class="hero">
        <div class="public-container hero-grid">
            <div class="hero-copy">
                <p class="eyebrow">{{ __('bloodcare.public.home.hero_eyebrow') }}</p>
                <h1>{{ __('bloodcare.public.home.hero_title') }}<br><span>{{ __('bloodcare.public.home.hero_highlight') }}</span></h1>
                <p>{{ __('bloodcare.public.home.hero_text') }}</p>

                <div class="hero-actions">
                    <a class="button button-primary" href="{{ route('donor.register') }}">
                        {{ __('bloodcare.public.home.become_donor') }}
                        <x-bloodcare-icon name="arrow" :size="22" />
                    </a>
                    <a class="button button-secondary" href="{{ route('appointments.book') }}">
                        <x-bloodcare-icon name="calendar" :size="22" />
                        {{ __('bloodcare.public.home.book_appointment') }}
                    </a>
                </div>

                <div class="hero-trust">
                    <span><x-bloodcare-icon name="check" :size="21" /> {{ __('bloodcare.public.home.trust_safe') }}</span>
                    <span><x-bloodcare-icon name="check" :size="21" /> {{ __('bloodcare.public.home.trust_verified') }}</span>
                    <span><x-bloodcare-icon name="check" :size="21" /> {{ __('bloodcare.public.home.trust_cards') }}</span>
                </div>
            </div>

            <div class="hero-visual" aria-label="{{ __('bloodcare.public.home.card_preview_aria') }}">
                <div class="hero-orbit" aria-hidden="true"></div>

                <div class="donor-card-visual">
                    <div class="visual-card-top">
                        <span><x-bloodcare-icon name="drop" :size="22" /> BloodCare</span>
                        <span>{{ __('bloodcare.public.home.active_donor') }}</span>
                    </div>
                    <div class="visual-card-main">
                        <div>
                            <small>{{ __('bloodcare.public.home.donor_name') }}</small>
                            <strong>Thiri Mon</strong>
                        </div>
                        <div class="visual-blood">O+</div>
                    </div>
                    <div class="visual-card-bottom">
                        <span>BC-002184</span>
                        <span>{{ __('bloodcare.public.home.sample_donations') }}</span>
                        <span>{{ __('bloodcare.public.home.eligible_now') }}</span>
                    </div>
                </div>

                <div class="floating-note note-appointment">
                    <x-bloodcare-icon name="calendar" :size="27" />
                    <span><strong>{{ __('bloodcare.public.home.next_appointment') }}</strong><small>{{ __('bloodcare.public.home.sample_appointment') }}</small></span>
                </div>
                <div class="floating-note note-impact">
                    <x-bloodcare-icon name="heart" :size="27" />
                    <span><strong>{{ __('bloodcare.public.home.lives_helped') }}</strong><small>{{ __('bloodcare.public.home.thank_you') }}</small></span>
                </div>
            </div>
        </div>
    </section>

    <section class="need-strip" aria-label="{{ __('bloodcare.public.home.priority_aria') }}" data-public-priority-strip>
        <div class="public-container need-strip-inner">
            <div class="need-label">
                <span class="pulse-dot"></span>
                <span>
                    <strong>{{ __('bloodcare.public.home.urgently_needed') }}</strong>
                    <small data-public-priority-source>{{ __('bloodcare.public.home.live_priorities') }}</small>
                </span>
            </div>
            <div class="need-groups" data-public-priority-groups>
                @foreach ($priorityGroups as $group)
                    <span class="blood-pill" title="{{ number_format((int) ($inventoryByGroup[$group] ?? 0)) }} available">{{ str_replace('-', '−', $group) }}</span>
                @endforeach
            </div>
            <a href="{{ route('appointments.book') }}">
                {{ __('bloodcare.public.home.book_donation') }} <x-bloodcare-icon name="arrow" :size="20" />
            </a>
        </div>
    </section>

    <section class="section">
        <div class="public-container">
            <div class="section-heading centered" data-reveal>
                <p class="eyebrow">{{ __('bloodcare.public.home.services_eyebrow') }}</p>
                <h2>{{ __('bloodcare.public.home.services_title') }}</h2>
                <p>{{ __('bloodcare.public.home.services_text') }}</p>
            </div>

            <div class="service-grid">
                <article class="service-card" data-reveal>
                    <span class="service-icon"><x-bloodcare-icon name="user" :size="31" /></span>
                    <h3>{{ __('bloodcare.public.home.register_title') }}</h3>
                    <p>{{ __('bloodcare.public.home.register_text') }}</p>
                    <a href="{{ route('donor.register') }}">{{ __('bloodcare.public.home.register_link') }} <x-bloodcare-icon name="arrow" :size="20" /></a>
                </article>

                <article class="service-card" data-reveal>
                    <span class="service-icon"><x-bloodcare-icon name="calendar" :size="31" /></span>
                    <h3>{{ __('bloodcare.public.home.appointment_title') }}</h3>
                    <p>{{ __('bloodcare.public.home.appointment_text') }}</p>
                    <a href="{{ route('appointments.book') }}">{{ __('bloodcare.public.home.appointment_link') }} <x-bloodcare-icon name="arrow" :size="20" /></a>
                </article>

                <article class="service-card" data-reveal>
                    <span class="service-icon"><x-bloodcare-icon name="card" :size="31" /></span>
                    <h3>{{ __('bloodcare.public.home.card_title') }}</h3>
                    <p>{{ __('bloodcare.public.home.card_text') }}</p>
                    <a href="{{ route('card.check') }}">{{ __('bloodcare.public.home.card_link') }} <x-bloodcare-icon name="arrow" :size="20" /></a>
                </article>
            </div>
        </div>
    </section>

    <section class="section section-soft">
        <div class="public-container impact-grid">
            <div class="impact-panel" data-reveal aria-hidden="true">
                <div class="impact-drop"><span>+</span></div>
                <div class="impact-stat impact-one"><strong>{{ __('bloodcare.public.home.average_time_value') }}</strong><small>{{ __('bloodcare.public.home.average_time_label') }}</small></div>
                <div class="impact-stat impact-two"><strong>{{ __('bloodcare.public.home.impact_value') }}</strong><small>{{ __('bloodcare.public.home.impact_label') }}</small></div>
            </div>

            <div class="content-heading" data-reveal>
                <p class="eyebrow">{{ __('bloodcare.public.home.process_eyebrow') }}</p>
                <h2>{{ __('bloodcare.public.home.process_title') }}</h2>
                <p>{{ __('bloodcare.public.home.process_text') }}</p>

                <div class="step-list">
                    <div class="step">
                        <span class="step-number">01</span>
                        <div><h3>{{ __('bloodcare.public.home.step_one_title') }}</h3><p>{{ __('bloodcare.public.home.step_one_text') }}</p></div>
                    </div>
                    <div class="step">
                        <span class="step-number">02</span>
                        <div><h3>{{ __('bloodcare.public.home.step_two_title') }}</h3><p>{{ __('bloodcare.public.home.step_two_text') }}</p></div>
                    </div>
                    <div class="step">
                        <span class="step-number">03</span>
                        <div><h3>{{ __('bloodcare.public.home.step_three_title') }}</h3><p>{{ __('bloodcare.public.home.step_three_text') }}</p></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section public-centres-section">
        <div class="public-container">
            <div class="section-heading public-centres-heading" data-reveal>
                <div>
                    <p class="eyebrow">{{ __('bloodcare.public.home.centres_eyebrow') }}</p>
                    <h2>{{ __('bloodcare.public.home.centres_title') }}</h2>
                    <p>{{ __('bloodcare.public.home.centres_text') }}</p>
                </div>
                <a class="button button-secondary" href="{{ route('appointments.book') }}">
                    <x-bloodcare-icon name="map" :size="23" />
                    {{ __('bloodcare.public.home.view_booking') }}
                </a>
            </div>

            <div class="public-centre-grid" data-public-centre-list>
                @forelse ($centres->take(6) as $centre)
                    <article class="public-centre-card" data-reveal>
                        <span class="public-centre-icon"><x-bloodcare-icon name="map" :size="28" /></span>
                        <div>
                            <h3>{{ $centre->name }}</h3>
                            <p>{{ collect([$centre->township, $centre->region])->filter()->join(' · ') }}</p>
                            <small>{{ __('bloodcare.public.home.centre_active') }}</small>
                        </div>
                        <a href="{{ route('appointments.book', ['centre' => $centre->name]) }}" aria-label="{{ __('bloodcare.public.home.book_at', ['centre' => $centre->name]) }}">
                            <x-bloodcare-icon name="arrow" :size="21" />
                        </a>
                    </article>
                @empty
                    <article class="public-centre-card" data-reveal>
                        <span class="public-centre-icon"><x-bloodcare-icon name="info" :size="28" /></span>
                        <div>
                            <h3>{{ __('bloodcare.public.home.no_active_centres_title') }}</h3>
                            <p>{{ __('bloodcare.public.home.no_active_centres_text') }}</p>
                        </div>
                    </article>
                @endforelse
            </div>

            <div class="public-centre-request" data-reveal>
                <x-bloodcare-icon name="info" :size="25" />
                <p>{{ __('bloodcare.public.home.no_centre') }}</p>
                <a href="{{ route('appointments.book') }}">{{ __('bloodcare.public.home.request_location') }} <x-bloodcare-icon name="arrow" :size="20" /></a>
            </div>
        </div>
    </section>

    <section class="stats-band" aria-label="{{ __('bloodcare.public.home.summary_aria') }}">
        <div class="public-container stats-grid">
            <div class="stat"><strong data-public-stat="donors">{{ number_format($homeStats['donors']) }}</strong><span>{{ __('bloodcare.public.home.stat_donors') }}</span></div>
            <div class="stat"><strong data-public-stat="donations">{{ number_format($homeStats['donations']) }}</strong><span>{{ __('bloodcare.public.home.stat_donations') }}</span></div>
            <div class="stat"><strong data-public-stat="inventory">{{ number_format($homeStats['inventory']) }}</strong><span>{{ __('bloodcare.public.home.stat_units') }}</span></div>
            <div class="stat"><strong data-public-stat="centres">{{ number_format($homeStats['centres']) }}</strong><span>{{ __('bloodcare.public.home.stat_centres') }}</span></div>
        </div>
        <p class="stats-source" data-public-stats-source>{{ __('bloodcare.public.home.live_totals') }}</p>
    </section>

    <section class="section">
        <div class="public-container">
            <div class="cta-panel" data-reveal>
                <div>
                    <h2>{{ __('bloodcare.public.home.cta_title') }}</h2>
                    <p>{{ __('bloodcare.public.home.cta_text') }}</p>
                </div>
                <a class="button button-secondary" href="{{ route('donor.register') }}">
                    {{ __('bloodcare.public.home.cta_button') }} <x-bloodcare-icon name="arrow" :size="22" />
                </a>
            </div>
        </div>
    </section>

    @php
        $publicHomeConfig = [
            'locale' => app()->getLocale(),
            'bookingUrl' => route('appointments.book'),
            'stats' => $homeStats,
            'priorityGroups' => $priorityGroups,
            'inventoryCounts' => $inventoryByGroup,
            'centres' => $centres->map(fn ($centre) => [
                'name' => $centre->name,
                'region' => $centre->region,
                'township' => $centre->township,
                'address' => $centre->address,
                'hours' => $centre->opening_hours,
                'active' => $centre->is_active,
                'bookingUrl' => route('appointments.book', ['centre' => $centre->name]),
            ])->values(),
            'labels' => [
                'livePriorities' => __('bloodcare.public.home.live_priorities'),
                'liveTotals' => __('bloodcare.public.home.live_totals'),
                'activeCentre' => __('bloodcare.public.home.centre_active'),
                'bookAt' => __('bloodcare.public.home.book_at_plain'),
                'locationUnavailable' => __('bloodcare.public.home.location_unavailable'),
            ],
        ];
    @endphp
    <script id="bc-public-home-config" type="application/json">@json($publicHomeConfig)</script>
@endsection
