<?php
namespace Clicalmani\Notification\Events;

use Clicalmani\Notification\NotificationContext;

/**
 * Class NotificationSkipped
 *
 * Event dispatched when a notification delivery is skipped on a specific channel
 * because the channel's `shouldSend()` evaluation returned false.
 *
 * @package Clicalmani\Notification\Events
 * @author clicalmani
 */
final class NotificationSkipped
{
    /**
     * Initializes a new NotificationSkipped event instance.
     *
     * @param string $channel Target channel name that was skipped.
     * @param NotificationContext $context Contextual metadata of the notification.
     */
    public function __construct(
        public readonly string $channel,
        public readonly NotificationContext $context,
    ) {}
}