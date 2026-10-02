<?php

namespace Clicalmani\Notification;

use Clicalmani\Notification\Concerns\Notifiable;

/**
 * Class AnonymousNotifiable
 *
 * Represents an on-the-fly, non-persisted recipient capable of receiving notifications
 * across dynamically configured delivery channels and addresses.
 *
 * @package Clicalmani\Notification
 * @author clicalmani
 */
final class AnonymousNotifiable
{
    use Notifiable;

    /** 
     * Map of targeted channel names to their corresponding delivery routes.
     * 
     * @var array<string, mixed>
     */
    private array $routes = [];

    /**
     * Binds a delivery target route to a specific channel.
     *
     * @param string $channel Target channel name (e.g., 'mail', 'slack', 'sms').
     * @param mixed $route Target destination endpoint (e.g., email address, webhook URL, phone number).
     * @return self
     */
    public function route(string $channel, mixed $route): self
    {
        $this->routes[$channel] = $route;

        return $this;
    }

    /**
     * Retrieves the target route address for a given notification channel.
     *
     * @param string $channel Target channel name to query.
     * @return mixed The configured route address if defined, or null otherwise.
     */
    public function routeNotificationFor(string $channel): mixed
    {
        return $this->routes[$channel] ?? null;
    }

    /**
     * Retrieves all registered route destinations mapped by channel.
     *
     * @return array<string, mixed> Map of channel names to route destinations.
     */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * Generates a unique synthetic identifier for the anonymous recipient instance.
     *
     * @return string Hashed identifier derived from registered route targets.
     */
    public function id(): string
    {
        return 'anonymous:' . md5(serialize($this->routes));
    }

    /**
     * Human-readable identifier name used for logging and notification events.
     *
     * @return string
     */
    public function getNotifiableName(): string
    {
        return 'anonymous';
    }

    /**
     * Dispatches a notification event to the configured ad-hoc routing targets.
     *
     * Intersects requested notification channels with explicitly configured routes
     * before delegating to the dispatcher pipeline.
     *
     * @param object $event The notification event instance.
     * @return void
     */
    public function notify(object $event): void
    {
        $event->via = fn() => array_values(
            array_intersect($event->via(), array_keys($this->routes))
        );

        (new NotificationDispatcher())->dispatch($event, $this);
    }
}