<?php

namespace Clicalmani\Notification\Channels;

use Clicalmani\Core\Support\Facades\DB;
use Clicalmani\Notification\Concerns\DefaultChannelBehavior;
use Clicalmani\Notification\Contracts\NotificationChannelInterface;
use Clicalmani\Notification\NotificationContext;

/**
 * Class DatabaseChannel
 *
 * Delivers notifications by persisting their payload directly into the database.
 *
 * @package Clicalmani\Notification\Channels
 * @author clicalmani
 */
final class DatabaseChannel implements NotificationChannelInterface
{
    use DefaultChannelBehavior;

    /**
     * Unique name identifier of the channel.
     *
     * @return string
     */
    public static function name(): string
    {
        return 'database';
    }

    /**
     * Channel execution priority level.
     * High priority (100) ensures database persistence occurs early in the notification pipeline.
     *
     * @return int
     */
    public static function priority(): int
    {
        return 100;
    }
    
    /**
     * Determines whether the channel should execute for the given context.
     * Verifies if the notification event implements the `toDatabase()` method.
     *
     * @param NotificationContext $context The notification context instance.
     * @return bool True if the `toDatabase` method exists, false otherwise.
     */
    public function shouldSend(NotificationContext $context): bool
    {
        return method_exists($context->event, 'toDatabase');
    }

    /**
     * Sends the notification by inserting its data representation into the notifications table.
     *
     * @param NotificationContext $context The notification context instance.
     * @return void
     */
    public function send(NotificationContext $context): void
    {
        $payload = [];

        if (method_exists($context->event, 'toDatabase')) {
            $payload = $context->event->toBroadcast();
        } elseif (method_exists($context->event, 'toArray')) {
            $payload = $context->event->toArray();
        };

        $success = DB::table('notifications')->insert($payload);
    }
}