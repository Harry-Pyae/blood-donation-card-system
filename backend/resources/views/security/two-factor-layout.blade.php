<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title') · BloodCare</title>
    @include('admin.partials.favicon')
    <script>document.documentElement.setAttribute('data-bs-theme', localStorage.colorMode ?? 'light');</script>
    <link rel="stylesheet" href="{{ asset('vendor/bloodcare-backpack/css/line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}?v={{ filemtime(public_path('css/bloodcare-admin.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/bloodcare-two-factor.css') }}?v={{ filemtime(public_path('css/bloodcare-two-factor.css')) }}">
    @stack('styles')
</head>
<body class="bc-twofactor-body">
    <div class="bc-twofactor-shell">
        <header class="bc-twofactor-topbar">
            <a class="bc-twofactor-brand" href="{{ route('home') }}"><span>+</span><strong><b>Blood</b>Care</strong></a>
            <div class="bc-twofactor-topbar-actions">
                @include('admin.partials.utility-controls')
                <a class="btn bc-btn-outline" href="{{ $backUrl }}"><i class="la la-arrow-left"></i>{{ __('bloodcare.two_factor.back') }}</a>
            </div>
        </header>

        <main class="bc-twofactor-content">
            @if(session('status'))
                <div class="bc-form-alert bc-form-alert-success" role="status"><i class="la la-check-circle"></i><span>{{ session('status') }}</span></div>
            @endif
            @if($errors->any())
                <div class="bc-form-alert bc-form-alert-error" role="alert"><i class="la la-exclamation-circle"></i><div><strong>{{ __('bloodcare.two_factor.fix_errors') }}</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
            @endif
            @yield('content')
        </main>
    </div>
    <script src="{{ asset('js/bloodcare-admin.js') }}?v={{ filemtime(public_path('js/bloodcare-admin.js')) }}" defer></script>
    @stack('scripts')
    @stack('after_scripts')
</body>
</html>
