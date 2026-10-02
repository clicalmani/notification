<?php
namespace Clicalmani\Notification\Events;

use Clicalmani\Notification\NotificationContext;

/**
 * Class NotificationSending
 *
 * Event dispatched immediately before a notification is delivered through a specific channel.
 *
 * @package Clicalmani\Notification\Events
 * @author clicalmani
 */
final class NotificationSending
{
    /**
     * Initializes a new NotificationSending event instance.
     *
     * @param string $channel Target channel name through which the notification is about to be sent.
     * @param NotificationContext $context Contextual metadata of the notification.
     */
    public function __construct(
        public readonly string $channel,
        public readonly NotificationContext $context,
    ) {}
}