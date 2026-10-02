<?php
namespace Clicalmani\Notification\Events;

use Clicalmani\Notification\NotificationContext;

/**
 * Class NotificationSent
 *
 * Event dispatched after a notification has been successfully delivered through a specific channel.
 *
 * @package Clicalmani\Notification\Events
 * @author clicalmani
 */
final class NotificationSent
{
    /**
     * Initializes a new NotificationSent event instance.
     *
     * @param string $channel Target channel name through which the notification was successfully sent.
     * @param NotificationContext $context Contextual metadata of the notification.
     */
    public function __construct(
        public readonly string $channel,
        public readonly NotificationContext $context,
    ) {}
}