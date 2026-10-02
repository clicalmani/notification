<?php

namespace Clicalmani\Notification\Channels;

use Broadcaster\BroadcastEvent;
use Clicalmani\Notification\Concerns\DefaultChannelBehavior;
use Clicalmani\Database\Support\Facades\Str;
use Clicalmani\Notification\Contracts\NotificationChannelInterface;
use Clicalmani\Notification\NotificationContext;

/**
 * Class BroadcastChannel
 *
 * Delivers notifications by broadcasting real-time websocket events target region channels.
 *
 * @package Clicalmani\Notification\Channels
 * @author clicalmani
 */
final class BroadcastChannel implements NotificationChannelInterface
{
    use DefaultChannelBehavior;

    /**
     * Unique name identifier of the channel.
     *
     * @return string
     */
    public static function name(): string
    {
        return 'broadcast';
    }

    /**
     * Channel execution priority level.
     * Higher priority ensures real-time broadcast events fire early.
     *
     * @return int
     */
    public static function priority(): int
    {
        return 10;
    }
    
    /**
     * Determines whether the channel should execute for the given context.
     * Checks if the notification event implements the `toBroadcast()` method.
     *
     * @param NotificationContext $context The notification context instance.
     * @return bool
     */
    public function shouldSend(NotificationContext $context): bool
    {
        return method_exists($context->event, 'toBroadcast');
    }

    /**
     * Sends the notification through the broadcasting system.
     *
     * @param NotificationContext $context The notification context instance.
     * @return void
     */
    public function send(NotificationContext $context): void
    {
        $payload = [];

        if (method_exists($context->event, 'toBroadcast')) {
            $payload = $context->event->toBroadcast();
        } elseif (method_exists($context->event, 'toArray')) {
            $payload = $context->event->toArray();
        };

        event(new BroadcastEvent(
            "{$context->table}.{$context->id}",
            "{$context->table}." . Str::tableize(basename($context->className)),
            $payload
        ));
    }
}