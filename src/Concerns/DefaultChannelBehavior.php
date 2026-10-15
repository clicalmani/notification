<?php

namespace Clicalmani\Notification\Concerns;

use Clicalmani\Notification\NotificationContext;

/**
 * Trait DefaultChannelBehavior
 *
 * Provides default implementations for channel priority and sending preconditions.
 * Can be mixed into concrete notification channel classes to reduce boilerplate code.
 *
 * @package Clicalmani\Notification\Concerns
 * @author clicalmani
 */
trait DefaultChannelBehavior
{
    /**
     * Default channel execution priority level.
     *
     * @return int Default priority value set to 0.
     */
    public function priority(): int
    {
        return 0;
    }

    /**
     * Determines whether the channel should execute for the given context.
     * Defaults to allowing dispatch unconditionally.
     *
     * @param NotificationContext $context The notification context instance.
     * @return bool Always returns true by default.
     */
    public function shouldSend(NotificationContext $context): bool
    {
        return true;
    }
}