<?php
namespace Clicalmani\Notification\Contracts;

use Clicalmani\Notification\NotificationContext;

/**
 * Interface ChannelMiddlewareInterface
 *
 * Defines the contract for channel middleware components capable of intercepting,
 * inspecting, or altering the notification context during pipeline execution.
 *
 * @package Clicalmani\Notification\Contracts
 * @author clicalmani
 */
interface ChannelMiddlewareInterface
{
    /**
     * Handles an incoming notification context and passes execution to the next middleware.
     *
     * @param NotificationContext $context The current notification context instance.
     * @param callable(NotificationContext): void $next Next execution step callback in the pipeline.
     * @return void
     */
    public function handle(NotificationContext $context, callable $next): void;
}