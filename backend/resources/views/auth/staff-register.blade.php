@extends(backpack_view('layouts.auth'))

@section('title', __('bloodcare.staff_register.page_title'))

@push('before_styles')
    @include('admin.partials.favicon')
@endpush

@push('after_styles')
    <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}?v={{ filemtime(public_path('css/bloodcare-admin.css')) }}">
@endpush

@push('after_scripts')
    <script>
        document.documentElement.classList.add('bc-staff-register-document');
        document.body.classList.add('bc-staff-register-body');
    </script>
@endpush

@section('content')
    <main class="bc-login-page bc-register-page"
          data-show-password="{{ __('bloodcare.staff_register.show_password') }}"
          data-hide-password="{{ __('bloodcare.staff_register.hide_password') }}"
          data-strength-weak="{{ __('bloodcare.staff_register.strength_weak') }}"
          data-strength-fair="{{ __('bloodcare.staff_register.strength_fair') }}"
          data-strength-good="{{ __('bloodcare.staff_register.strength_good') }}"
          data-strength-strong="{{ __('bloodcare.staff_register.strength_strong') }}"
          data-password-invalid="{{ __('bloodcare.staff_register.password_invalid') }}"
          data-passwords-match="{{ __('bloodcare.staff_register.passwords_match') }}"
          data-passwords-mismatch="{{ __('bloodcare.staff_register.passwords_mismatch') }}">
        <div class="bc-login-shell bc-register-shell">
            <section class="bc-login-brand-panel" aria-label="{{ __('bloodcare.staff_register.secure_title') }}">
                <a class="bc-login-brand" href="{{ route('home') }}" aria-label="BloodCare">
                    <span class="bc-login-brand-mark">+</span>
                    <span><strong>Blood</strong>Care</span>
                </a>
                <div class="bc-login-brand-copy">
                    <span class="bc-login-drop" aria-hidden="true"><i class="la la-user-plus"></i></span>
                    <p class="bc-login-eyebrow">{{ __('bloodcare.staff_register.eyebrow') }}</p>
                    <h1>{{ __('bloodcare.staff_register.secure_title') }}</h1>
                    <p>{{ __('bloodcare.staff_register.secure_text') }}</p>
                    <ul class="bc-login-features">
                        <li><i class="la la-check"></i><span>{{ __('bloodcare.staff_register.feature_review') }}</span></li>
                        <li><i class="la la-check"></i><span>{{ __('bloodcare.staff_register.feature_access') }}</span></li>
                        <li><i class="la la-check"></i><span>{{ __('bloodcare.staff_register.feature_security') }}</span></li>
                    </ul>
                </div>
                <p class="bc-login-brand-footer">BloodCare Management System</p>
            </section>

            <section class="bc-login-form-panel bc-register-form-panel">
                <div class="bc-login-toolbar">@include('admin.partials.utility-controls')</div>
                <div class="bc-login-card bc-register-card">
                    <div class="bc-login-mobile-brand" aria-hidden="true">
                        <span class="bc-login-brand-mark">+</span><span><strong>Blood</strong>Care</span>
                    </div>
                    <p class="bc-login-eyebrow">{{ __('bloodcare.staff_register.eyebrow') }}</p>
                    <h2>{{ __('bloodcare.staff_register.title') }}</h2>
                    <p class="bc-login-subtitle">{{ __('bloodcare.staff_register.subtitle') }}</p>

                    @if ($errors->any())
                        <div class="bc-auth-notice bc-auth-notice-error" role="alert">
                            <i class="la la-exclamation-circle"></i>
                            <span>{{ __('bloodcare.staff_register.fix_errors') }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('bloodcare.staff.register.store') }}" autocomplete="off">
                        @csrf
                        <div class="bc-register-grid">
                            <div class="bc-login-field bc-register-span-2">
                                <label for="name">{{ __('bloodcare.staff_register.name') }}</label>
                                <div class="bc-login-input"><i class="la la-user"></i>
                                    <input id="name" name="name" type="text" required autofocus maxlength="120" autocomplete="name"
                                           value="{{ old('name') }}" placeholder="{{ __('bloodcare.staff_register.name_placeholder') }}"
                                           class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}">
                                </div>
                                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="bc-login-field">
                                <label for="email">{{ __('bloodcare.staff_register.email') }}</label>
                                <div class="bc-login-input"><i class="la la-envelope"></i>
                                    <input id="email" name="email" type="email" required maxlength="190" autocomplete="email"
                                           value="{{ old('email') }}" placeholder="{{ __('bloodcare.staff_register.email_placeholder') }}"
                                           class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}">
                                </div>
                                @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="bc-login-field">
                                <label for="phone">{{ __('bloodcare.staff_register.phone') }}</label>
                                <div class="bc-login-input"><i class="la la-phone"></i>
                                    <input id="phone" name="phone" type="tel" required maxlength="30" autocomplete="tel"
                                           value="{{ old('phone') }}" placeholder="{{ __('bloodcare.staff_register.phone_placeholder') }}"
                                           class="form-control {{ $errors->has('phone') ? 'is-invalid' : '' }}">
                                </div>
                                @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="bc-login-field">
                                <label for="job_title">{{ __('bloodcare.staff_register.job_title') }}</label>
                                <div class="bc-login-input"><i class="la la-id-badge"></i>
                                    <input id="job_title" name="job_title" type="text" required maxlength="100"
                                           value="{{ old('job_title') }}" placeholder="{{ __('bloodcare.staff_register.job_title_placeholder') }}"
                                           class="form-control {{ $errors->has('job_title') ? 'is-invalid' : '' }}">
                                </div>
                                @error('job_title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="bc-login-field">
                                <label for="workplace">{{ __('bloodcare.staff_register.workplace') }}</label>
                                <div class="bc-login-input"><i class="la la-hospital"></i>
                                    <input id="workplace" name="workplace" type="text" required maxlength="150"
                                           value="{{ old('workplace') }}" placeholder="{{ __('bloodcare.staff_register.workplace_placeholder') }}"
                                           class="form-control {{ $errors->has('workplace') ? 'is-invalid' : '' }}">
                                </div>
                                @error('workplace')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="bc-login-field">
                                <label for="register-password">{{ __('bloodcare.staff_register.password') }}</label>
                                <div class="bc-login-input"><i class="la la-lock"></i>
                                    <input id="register-password" name="password" type="password" required minlength="8" autocomplete="new-password"
                                           aria-describedby="bc-register-password-strength bc-register-password-requirements"
                                           placeholder="{{ __('bloodcare.staff_register.password_placeholder') }}"
                                           class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}">
                                    <button class="bc-password-toggle" type="button" data-password-toggle data-password-target="register-password"
                                            aria-label="{{ __('bloodcare.staff_register.show_password') }}" aria-pressed="false"><i class="la la-eye"></i></button>
                                </div>
                                <div class="bc-password-strength" id="bc-register-password-strength" aria-live="polite">
                                    <span class="bc-password-strength-track" aria-hidden="true">
                                        <span id="bc-register-password-strength-bar"></span>
                                    </span>
                                    <span>
                                        {{ __('bloodcare.staff_register.password_strength') }}:
                                        <strong id="bc-register-password-strength-label">{{ __('bloodcare.staff_register.strength_weak') }}</strong>
                                    </span>
                                </div>
                                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="bc-login-field">
                                <label for="password-confirmation">{{ __('bloodcare.staff_register.confirm_password') }}</label>
                                <div class="bc-login-input"><i class="la la-lock"></i>
                                    <input id="password-confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"
                                           aria-describedby="bc-register-password-match"
                                           placeholder="{{ __('bloodcare.staff_register.confirm_placeholder') }}" class="form-control">
                                    <button class="bc-password-toggle" type="button" data-password-toggle data-password-target="password-confirmation"
                                            aria-label="{{ __('bloodcare.staff_register.show_password') }}" aria-pressed="false"><i class="la la-eye"></i></button>
                                </div>
                                <small class="bc-password-match" id="bc-register-password-match" aria-live="polite"></small>
                            </div>

                            <div class="bc-password-requirements bc-register-password-requirements bc-register-span-2"
                                 id="bc-register-password-requirements">
                                <strong>{{ __('bloodcare.staff_register.password_requirements') }}</strong>
                                <ul>
                                    <li data-register-password-rule="length"><i class="la la-circle" aria-hidden="true"></i>{{ __('bloodcare.staff_register.requirement_length') }}</li>
                                    <li data-register-password-rule="lower"><i class="la la-circle" aria-hidden="true"></i>{{ __('bloodcare.staff_register.requirement_lower') }}</li>
                                    <li data-register-password-rule="upper"><i class="la la-circle" aria-hidden="true"></i>{{ __('bloodcare.staff_register.requirement_upper') }}</li>
                                    <li data-register-password-rule="number"><i class="la la-circle" aria-hidden="true"></i>{{ __('bloodcare.staff_register.requirement_number') }}</li>
                                </ul>
                            </div>

                            <div class="bc-login-field bc-register-span-2">
                                <label for="registration_note">{{ __('bloodcare.staff_register.note') }}</label>
                                <textarea id="registration_note" name="registration_note" maxlength="500" rows="3"
                                          placeholder="{{ __('bloodcare.staff_register.note_placeholder') }}"
                                          class="form-control bc-register-textarea {{ $errors->has('registration_note') ? 'is-invalid' : '' }}">{{ old('registration_note') }}</textarea>
                                @error('registration_note')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <label class="bc-register-terms">
                            <input name="terms" type="checkbox" value="1" required {{ old('terms') ? 'checked' : '' }} class="form-check-input">
                            <span>{{ __('bloodcare.staff_register.terms') }}</span>
                        </label>
                        @error('terms')<div class="invalid-feedback d-block mb-3">{{ $message }}</div>@enderror

                        <button type="submit" class="btn bc-login-submit"><span>{{ __('bloodcare.staff_register.submit') }}</span><i class="la la-arrow-right"></i></button>
                    </form>

                    <div class="bc-auth-switch"><span>{{ __('bloodcare.staff_register.have_account') }}</span><a href="{{ route('backpack.auth.login') }}">{{ __('bloodcare.staff_register.sign_in') }}</a></div>
                    <a class="bc-login-back" href="{{ route('home') }}"><i class="la la-arrow-left"></i>{{ __('bloodcare.login.back_home') }}</a>
                </div>
            </section>
        </div>
    </main>
@endsection

@push('after_scripts')
    <script src="{{ asset('js/bloodcare-auth.js') }}?v={{ filemtime(public_path('js/bloodcare-auth.js')) }}" defer></script>
@endpush
