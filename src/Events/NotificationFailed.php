<?php
namespace Clicalmani\Notification\Events;

use Clicalmani\Notification\NotificationContext;
use Throwable;

/**
 * Class NotificationFailed
 *
 * Event dispatched when a notification delivery fails on a specific channel due to an exception.
 *
 * @package Clicalmani\Notification\Events
 * @author clicalmani
 */
final class NotificationFailed
{
    /**
     * Initializes a new NotificationFailed event instance.
     *
     * @param string $channel Target channel name where delivery failed.
     * @param NotificationContext $context Contextual metadata of the notification.
     * @param Throwable $exception The thrown exception that caused the failure.
     */
    public function __construct(
        public readonly string $channel,
        public readonly NotificationContext $context,
        public readonly Throwable $exception,
    ) {}
}