<?php

namespace Clicalmani\Notification;

use Clicalmani\Notification\Concerns\Notifiable;

/**
 * Class NotificationRoute
 *
 * Represents an ad-hoc or direct notification recipient definition, routing a specific 
 * delivery channel to an explicit endpoint target (such as an email address, webhook URL, or phone number).
 *
 * @package Clicalmani\Notification
 * @author clicalmani
 */
final class NotificationRoute
{
    use Notifiable;

    /**
     * Initializes a new NotificationRoute instance.
     *
     * @param string $channel Target channel name (e.g., 'mail', 'slack', 'sms').
     * @param mixed $route Target endpoint address (e.g., email address, webhook URL, phone number).
     */
    public function __construct(
        public readonly string $channel,
        public readonly mixed $route,
    ) {}

    /**
     * Retrieves the target route address for a given notification channel.
     * Used by specific delivery channels (e.g., broadcast channels) as room or destination keys.
     *
     * @param string $channel Target channel name to query.
     * @return mixed The configured route address if the channel matches, or null otherwise.
     */
    public function routeNotificationFor(string $channel): mixed
    {
        return $channel === $this->channel ? $this->route : null;
    }

    /**
     * Generates a synthetic unique identifier for the notification context.
     *
     * @return string Hashed identifier string.
     */
    public function id(): string
    {
        return sprintf('%s:%s', $this->channel, md5(serialize($this->route)));
    }
}