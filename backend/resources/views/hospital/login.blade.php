<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ __('bloodcare.national.portal.hospital_portal') }} · BloodCare</title><link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bloodcare-backpack/css/line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}">
</head>
<body class="bc-hospital-body"><main class="bc-hospital-login"><section class="bc-hospital-login-card">
    <div class="bc-hospital-brand"><span><i class="la la-heartbeat"></i></span><div><strong>BloodCare</strong><small>{{ __('bloodcare.national.portal.hospital_services') }}</small></div></div>
    @include('hospital.language-switch')
    <h1>{{ __('bloodcare.national.portal.hospital_portal') }}</h1><p>{{ __('bloodcare.national.portal.secure_access') }}</p>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('hospital.login.store') }}" class="bc-national-form">@csrf
        <label>{{ __('bloodcare.national.portal.work_email') }}<input type="email" name="email" value="{{ old('email') }}" required autofocus></label>
        <label>{{ __('bloodcare.national.portal.password') }}<input type="password" name="password" required></label>
        <button class="btn bc-btn-primary">{{ __('bloodcare.national.portal.sign_in') }}</button>
    </form><a href="{{ route('home') }}">← {{ __('bloodcare.national.portal.return') }}</a>
</section></main></body></html>
