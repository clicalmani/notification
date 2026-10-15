<?php

namespace Clicalmani\Notification\Contracts;

use Clicalmani\Notification\NotificationContext;

/**
 * Interface NotificationChannelInterface
 *
 * Defines the contract for all notification channels responsible for delivering
 * notifications, managing execution priority, and validating sending conditions.
 *
 * @package Clicalmani\Notification\Contracts
 */
interface NotificationChannelInterface
{
    /**
     * Unique name identifier of the channel.
     *
     * @return string
     */
    public static function name(): string;

    /**
     * Sends the notification through this channel.
     *
     * @param NotificationContext $context The notification context instance.
     * @return void
     */
    public function send(NotificationContext $context): void;

    /**
     * Execution priority level — higher values execute earlier. Default: 0.
     *
     * @return int
     */
    public function priority(): int;

    /**
     * Determines whether the channel should execute for the given context.
     * Useful for business rules (e.g., verified email, user opt-in, preference flags).
     *
     * @param NotificationContext $context The notification context instance.
     * @return bool
     */
    public function shouldSend(NotificationContext $context): bool;
}