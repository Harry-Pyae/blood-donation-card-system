<form class="bc-hospital-language-switch" method="POST" action="{{ route('language.switch') }}" aria-label="{{ __('bloodcare.national.portal.language') }}">
    @csrf
    <button type="submit" name="locale" value="en" class="{{ app()->isLocale('en') ? 'active' : '' }}" lang="en">EN</button>
    <span aria-hidden="true">/</span>
    <button type="submit" name="locale" value="my" class="{{ app()->isLocale('my') ? 'active' : '' }}" lang="my">မြန်မာ</button>
</form>
