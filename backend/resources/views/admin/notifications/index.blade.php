@extends(backpack_view('blank'))

@section('title', __('bloodcare.notifications.page_title'))

@push('before_styles')
    @include('admin.partials.favicon')
@endpush

@push('after_styles')
    <link rel="stylesheet" href="{{ asset('css/bloodcare-admin.css') }}?v={{ filemtime(public_path('css/bloodcare-admin.css')) }}">
@endpush

@section('content')
<div class="bc-admin-page bc-notification-page">
    <header class="bc-page-heading">
        <div>
            <p class="bc-eyebrow">{{ __('bloodcare.notifications.eyebrow') }}</p>
            <h1>{{ __('bloodcare.notifications.title') }}</h1>
            <p>{{ __('bloodcare.notifications.subtitle') }}</p>
        </div>
        <div class="bc-heading-tools">
            @include('admin.partials.utility-controls')
        </div>
    </header>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <section class="bc-module-metrics bc-notification-metrics" aria-label="{{ __('bloodcare.notifications.summary') }}">
        <article class="bc-mini-metric"><span class="bc-mini-dot bc-dot-red"></span><div><small>{{ __('bloodcare.notifications.unread') }}</small><strong>{{ $unreadCount }}</strong></div></article>
        <article class="bc-mini-metric"><span class="bc-mini-dot bc-dot-blue"></span><div><small>{{ __('bloodcare.notifications.total') }}</small><strong>{{ $totalCount }}</strong></div></article>
    </section>

    <section class="bc-panel bc-notification-panel">
        <div class="bc-notification-toolbar">
            <nav class="bc-notification-filters" aria-label="{{ __('bloodcare.notifications.filter_label') }}">
                <a class="{{ $filter === 'all' ? 'active' : '' }}" href="{{ route($routePrefix, ['filter' => 'all']) }}">{{ __('bloodcare.notifications.all') }}</a>
                <a class="{{ $filter === 'unread' ? 'active' : '' }}" href="{{ route($routePrefix, ['filter' => 'unread']) }}">{{ __('bloodcare.notifications.unread') }} <span>{{ $unreadCount }}</span></a>
            </nav>
            @if($unreadCount > 0)
                <form method="POST" action="{{ route($routePrefix.'.read-all') }}">@csrf @method('PATCH')
                    <button class="btn bc-btn-outline" type="submit"><i class="la la-check-double"></i> {{ __('bloodcare.notifications.mark_all_read') }}</button>
                </form>
            @endif
        </div>

        <div class="bc-notification-list">
            @forelse($notifications as $notification)
                @php
                    $data = is_array($notification->data) ? $notification->data : [];
                    $level = in_array(($data['level'] ?? 'info'), ['info','success','warning','danger'], true) ? $data['level'] : 'info';
                    $titleKey = $data['title_key'] ?? 'bloodcare.notifications.fallback_title';
                    $messageKey = $data['message_key'] ?? 'bloodcare.notifications.fallback_message';
                    $parameters = is_array($data['parameters'] ?? null) ? $data['parameters'] : [];
                    if (isset($parameters['component'])) $parameters['component'] = __('bloodcare.national.components.types.'.$parameters['component']);
                    if (isset($parameters['priority'])) $parameters['priority'] = __('bloodcare.national.portal.'.$parameters['priority']);
                    $targetRoute = $data['route_name'] ?? null;
                    $hasTarget = is_string($targetRoute) && \Illuminate\Support\Facades\Route::has($targetRoute);
                @endphp
                <article class="bc-notification-item bc-notification-{{ $level }} {{ $notification->read_at ? 'is-read' : 'is-unread' }}">
                    <span class="bc-notification-icon" aria-hidden="true">
                        <i class="la {{ $level === 'danger' ? 'la-exclamation-triangle' : ($level === 'warning' ? 'la-exclamation-circle' : ($level === 'success' ? 'la-check-circle' : 'la-bell')) }}"></i>
                    </span>
                    <div class="bc-notification-copy">
                        <div class="bc-notification-title-line">
                            <strong>{{ __($titleKey, $parameters) }}</strong>
                            @unless($notification->read_at)<span>{{ __('bloodcare.notifications.new') }}</span>@endunless
                        </div>
                        <p>{{ __($messageKey, $parameters) }}</p>
                        <small><i class="la la-clock"></i> {{ $notification->created_at?->diffForHumans() }}</small>
                    </div>
                    <div class="bc-notification-actions">
                        @if($hasTarget)
                            <form method="POST" action="{{ route($routePrefix.'.open', $notification->id) }}">@csrf @method('PATCH')
                                <button class="btn btn-sm bc-btn-outline" type="submit">{{ __('bloodcare.notifications.open') }} <i class="la la-arrow-right"></i></button>
                            </form>
                        @endif
                        @unless($notification->read_at)
                            <form method="POST" action="{{ route($routePrefix.'.read', $notification->id) }}">@csrf @method('PATCH')
                                <button class="btn btn-sm bc-notification-read-button" type="submit"><i class="la la-check"></i> {{ __('bloodcare.notifications.mark_read') }}</button>
                            </form>
                        @endunless
                    </div>
                </article>
            @empty
                <div class="bc-notification-empty">
                    <i class="la la-bell-slash"></i>
                    <strong>{{ __('bloodcare.notifications.empty_title') }}</strong>
                    <p>{{ __('bloodcare.notifications.empty_text') }}</p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="bc-module-pagination">{{ $notifications->links() }}</div>
        @endif
    </section>
</div>
@endsection
