<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#d91f3a">
    <meta name="description" content="@yield('meta_description', __('bloodcare.public.meta_default'))">
    <meta name="robots" content="@yield('robots', 'index,follow')">

    <title>@yield('title', 'BloodCare') · {{ __('bloodcare.public.brand_subtitle') }}</title>

    @include('admin.partials.favicon')
    <script>
        (() => {
            try {
                const saved = localStorage.getItem('bloodcare.public.theme.v1');
                const preferred = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                document.documentElement.dataset.theme = saved === 'dark' || saved === 'light' ? saved : preferred;
            } catch (error) {
                document.documentElement.dataset.theme = 'light';
            }
        })();
    </script>
    <script>
        document.documentElement.classList.add('bc-page-loading');
        window.setTimeout(() => document.documentElement.classList.remove('bc-page-loading'), 3500);
    </script>
    <link rel="stylesheet" href="{{ asset('css/bloodcare-public.css') }}?v={{ filemtime(public_path('css/bloodcare-public.css')) }}">
</head>
<body class="@yield('body_class')">
    <div class="bc-global-loader" id="bc-global-loader" aria-hidden="false">
        <div class="bc-loader-panel" role="status" aria-live="polite">
            <span class="bc-loader-spinner" aria-hidden="true"></span>
            <div class="bc-loader-copy">
                <strong>{{ app()->isLocale('my') ? 'စာမျက်နှာ ပြင်ဆင်နေသည်' : 'Preparing the page' }}</strong>
                <span>{{ app()->isLocale('my') ? 'ခဏလေး စောင့်ပါ…' : 'Please wait a moment…' }}</span>
            </div>
            <div class="bc-loader-skeleton" aria-hidden="true">
                <i></i><i></i><i></i>
            </div>
        </div>
    </div>

    <a class="skip-link" href="#main-content">{{ __('bloodcare.public.skip_content') }}</a>

    <header class="public-header" data-public-header>
        <div class="public-container header-inner">
            <a class="brand" href="{{ route('home') }}" aria-label="{{ __('bloodcare.public.home_aria') }}">
                <span class="brand-mark"><x-bloodcare-icon name="drop" :size="28" /></span>
                <span class="brand-copy"><strong>BloodCare</strong><small>{{ __('bloodcare.public.brand_subtitle') }}</small></span>
            </a>

            <button
                class="nav-toggle"
                type="button"
                aria-label="{{ __('bloodcare.public.open_navigation') }}"
                aria-expanded="false"
                aria-controls="public-navigation"
                data-nav-toggle
                data-open-label="{{ __('bloodcare.public.open_navigation') }}"
                data-close-label="{{ __('bloodcare.public.close_navigation') }}"
            >
                <span class="nav-open-icon"><x-bloodcare-icon name="menu" :size="26" /></span>
                <span class="nav-close-icon"><x-bloodcare-icon name="close" :size="26" /></span>
            </button>

            <nav class="public-nav" id="public-navigation" aria-label="{{ __('bloodcare.public.main_navigation') }}" data-public-nav>
                <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}" @if(request()->routeIs('home')) aria-current="page" @endif>{{ __('bloodcare.public.nav.home') }}</a>
                <a href="{{ route('donor.register') }}" class="{{ request()->routeIs('donor.*') ? 'active' : '' }}" @if(request()->routeIs('donor.*')) aria-current="page" @endif>{{ __('bloodcare.public.nav.donor') }}</a>
                <a href="{{ route('appointments.book') }}" class="{{ request()->routeIs('appointments.*') ? 'active' : '' }}" @if(request()->routeIs('appointments.*')) aria-current="page" @endif>{{ __('bloodcare.public.nav.appointments') }}</a>
                <a href="{{ route('card.check') }}" class="{{ request()->routeIs('card.*') ? 'active' : '' }}" @if(request()->routeIs('card.*')) aria-current="page" @endif>{{ __('bloodcare.public.nav.card') }}</a>
                <a href="{{ route('eligibility') }}" class="{{ request()->routeIs('eligibility') ? 'active' : '' }}" @if(request()->routeIs('eligibility')) aria-current="page" @endif>{{ __('bloodcare.public.nav.eligibility') }}</a>
                <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}" @if(request()->routeIs('about')) aria-current="page" @endif>{{ __('bloodcare.public.nav.about') }}</a>
                <a href="{{ route('hospital.login') }}" class="{{ request()->routeIs('hospital.*') ? 'active' : '' }}" @if(request()->routeIs('hospital.*')) aria-current="page" @endif>{{ __('bloodcare.public.nav.hospital_portal') }}</a>
                <a class="public-nav-staff" href="{{ backpack_url('login') }}">
                    <x-bloodcare-icon name="user" :size="21" />
                    {{ __('bloodcare.public.staff_login') }}
                </a>
            </nav>

            <div class="public-tools" aria-label="{{ __('bloodcare.controls.preferences') }}">
                <button
                    class="public-tool-button public-theme-toggle"
                    type="button"
                    data-public-theme-toggle
                    data-light-label="{{ __('bloodcare.controls.use_light') }}"
                    data-dark-label="{{ __('bloodcare.controls.use_dark') }}"
                    aria-label="{{ __('bloodcare.controls.use_dark') }}"
                >
                    <span class="theme-sun"><x-bloodcare-icon name="sun" :size="23" /></span>
                    <span class="theme-moon"><x-bloodcare-icon name="moon" :size="23" /></span>
                </button>

                <form class="public-language-form" method="POST" action="{{ route('language.switch') }}" data-public-language-form>
                    @csrf
                    <details class="public-language-menu" data-public-language-menu>
                        <summary aria-label="{{ __('bloodcare.controls.language') }}">
                            <x-bloodcare-icon name="globe" :size="21" />
                            <span>{{ app()->isLocale('my') ? 'မြန်မာ' : 'EN' }}</span>
                            <x-bloodcare-icon class="language-chevron" name="chevron" :size="16" />
                        </summary>
                        <div class="public-language-options" role="group" aria-label="{{ __('bloodcare.controls.language') }}">
                            <button
                                type="submit"
                                name="locale"
                                value="en"
                                class="{{ app()->isLocale('en') ? 'active' : '' }}"
                                @if(app()->isLocale('en')) aria-current="true" @endif
                            >
                                <span>English</span>
                                @if(app()->isLocale('en')) <x-bloodcare-icon name="check" :size="18" /> @endif
                            </button>
                            <button
                                type="submit"
                                name="locale"
                                value="my"
                                class="{{ app()->isLocale('my') ? 'active' : '' }}"
                                @if(app()->isLocale('my')) aria-current="true" @endif
                            >
                                <span>မြန်မာ</span>
                                @if(app()->isLocale('my')) <x-bloodcare-icon name="check" :size="18" /> @endif
                            </button>
                        </div>
                    </details>
                </form>
            </div>

            <a class="staff-login" href="{{ backpack_url('login') }}" aria-label="{{ __('bloodcare.public.staff_login') }}">
                <x-bloodcare-icon name="user" :size="22" />
                <span>{{ __('bloodcare.public.staff_login') }}</span>
            </a>
        </div>
    </header>

    <main id="main-content">
        @yield('content')
    </main>

    <footer class="public-footer">
        <div class="public-container footer-grid">
            <div class="footer-brand">
                <a class="brand brand-light" href="{{ route('home') }}">
                    <span class="brand-mark"><x-bloodcare-icon name="drop" :size="28" /></span>
                    <span class="brand-copy"><strong>BloodCare</strong><small>{{ __('bloodcare.public.brand_subtitle') }}</small></span>
                </a>
                <p>{{ __('bloodcare.public.footer.summary') }}</p>
            </div>
            <div>
                <h2>{{ __('bloodcare.public.footer.services') }}</h2>
                <a href="{{ route('donor.register') }}">{{ __('bloodcare.public.footer.register') }}</a>
                <a href="{{ route('appointments.book') }}">{{ __('bloodcare.public.footer.book') }}</a>
                <a href="{{ route('card.check') }}">{{ __('bloodcare.public.footer.card') }}</a>
                <a href="{{ route('eligibility') }}">{{ __('bloodcare.public.footer.eligibility') }}</a>
                <a href="{{ route('hospital.login') }}">{{ __('bloodcare.public.footer.hospital_portal') }}</a>
            </div>
            <div>
                <h2>{{ __('bloodcare.public.footer.contact') }}</h2>
                <p><x-bloodcare-icon name="phone" :size="21" /> 01 555 0123</p>
                <p><x-bloodcare-icon name="map" :size="21" /> {{ __('bloodcare.public.footer.location') }}</p>
                <p><x-bloodcare-icon name="clock" :size="21" /> {{ __('bloodcare.public.footer.hours') }}</p>
            </div>
        </div>
        <div class="public-container footer-bottom">
            <span>&copy; {{ date('Y') }} BloodCare. {{ __('bloodcare.public.footer.prototype') }}</span>
            <a href="{{ backpack_url('login') }}">{{ __('bloodcare.public.footer.workspace') }}</a>
        </div>
    </footer>

    <script src="{{ asset('js/bloodcare-public.js') }}?v={{ filemtime(public_path('js/bloodcare-public.js')) }}" defer></script>
    <script src="{{ asset('js/bloodcare-form-controls.js') }}?v={{ filemtime(public_path('js/bloodcare-form-controls.js')) }}" defer></script>
</body>
</html>
