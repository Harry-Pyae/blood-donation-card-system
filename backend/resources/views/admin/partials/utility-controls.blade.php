<div class="bc-utility-controls" aria-label="{{ __('bloodcare.controls.preferences') }}">
    @php
        $utilityUser = $bloodcareWorkspaceUser
            ?? \Illuminate\Support\Facades\Auth::guard((string) config('backpack.base.guard', 'web'))->user()
            ?? request()->user();
        $utilityNotificationRoute = $utilityUser?->isLaboratoryUser()
            ? 'bloodcare.lab.notifications'
            : 'bloodcare.admin.notifications';
        $utilityNotificationCountRoute = $utilityNotificationRoute.'.unread-count';
        $utilityUnreadCount = $utilityUser?->unreadNotifications()->count() ?? 0;
    @endphp
    @if($utilityUser?->canAccessStaffWorkspace() && ! $utilityUser?->isHospitalUser())
        <a
            class="bc-admin-tool-button bc-notification-tool-button"
            href="{{ route($utilityNotificationRoute) }}"
            aria-label="{{ __('bloodcare.notifications.open_with_count', ['count' => $utilityUnreadCount]) }}"
            title="{{ __('bloodcare.notifications.title') }}"
            data-bc-notification-tool
            data-count-url="{{ route($utilityNotificationCountRoute) }}"
            data-count-label="{{ __('bloodcare.notifications.open_with_count', ['count' => '__COUNT__']) }}"
        >
            <x-bloodcare-icon name="bell" :size="22" />
            <span data-bc-notification-count @if($utilityUnreadCount === 0) hidden @endif>{{ $utilityUnreadCount > 99 ? '99+' : $utilityUnreadCount }}</span>
        </a>
    @endif

    <button
        class="bc-admin-tool-button bc-admin-theme-toggle"
        type="button"
        data-bc-theme-toggle
        data-light-label="{{ __('bloodcare.controls.use_light') }}"
        data-dark-label="{{ __('bloodcare.controls.use_dark') }}"
        aria-label="{{ __('bloodcare.controls.use_dark') }}"
    >
        <span class="bc-admin-theme-sun"><x-bloodcare-icon name="sun" :size="23" /></span>
        <span class="bc-admin-theme-moon"><x-bloodcare-icon name="moon" :size="23" /></span>
    </button>

    <form
        class="bc-admin-language-form"
        method="POST"
        action="{{ route('language.switch') }}"
        data-bc-language-form
    >
        @csrf
        <details class="bc-admin-language-menu" data-bc-language-menu>
            <summary aria-label="{{ __('bloodcare.controls.language') }}">
                <x-bloodcare-icon name="globe" :size="21" />
                <span>{{ app()->isLocale('my') ? 'မြန်မာ' : 'EN' }}</span>
                <x-bloodcare-icon class="bc-admin-language-chevron" name="chevron" :size="16" />
            </summary>
            <div class="bc-admin-language-options" role="group" aria-label="{{ __('bloodcare.controls.language') }}">
                <button
                    type="submit"
                    name="locale"
                    value="en"
                    class="{{ app()->isLocale('en') ? 'active' : '' }}"
                    lang="en"
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
                    lang="my"
                    @if(app()->isLocale('my')) aria-current="true" @endif
                >
                    <span>မြန်မာ</span>
                    @if(app()->isLocale('my')) <x-bloodcare-icon name="check" :size="18" /> @endif
                </button>
            </div>
        </details>
    </form>
</div>

@once
    @push('after_scripts')
        <script src="{{ asset('js/bloodcare-notifications.js') }}?v={{ filemtime(public_path('js/bloodcare-notifications.js')) }}" defer></script>
    @endpush
@endonce
