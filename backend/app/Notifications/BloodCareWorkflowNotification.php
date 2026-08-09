<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BloodCareWorkflowNotification extends Notification
{
    use Queueable;

    /**
     * Translation keys are stored instead of rendered English/Myanmar text so
     * the same notification follows the recipient's current language choice.
     *
     * @param array<string, scalar|null> $parameters
     */
    public function __construct(
        public readonly string $event,
        public readonly string $level,
        public readonly array $parameters = [],
        public readonly ?string $routeName = null,
        public readonly array $routeParameters = [],
        public readonly array $routeQuery = [],
        public readonly ?string $routeFragment = null,
    ) {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'level' => $this->level,
            'title_key' => "bloodcare.notifications.events.{$this->event}.title",
            'message_key' => "bloodcare.notifications.events.{$this->event}.message",
            'parameters' => $this->parameters,
            'route_name' => $this->routeName,
            'route_parameters' => $this->routeParameters,
            'route_query' => $this->routeQuery,
            'route_fragment' => $this->routeFragment,
        ];
    }
}
