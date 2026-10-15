<?php
namespace Clicalmani\Notification;

use BackedEnum;

/**
 * Class NotificationContext
 *
 * Represents the contextual metadata required to process and dispatch a notification event
 * to specified channels for a given notifiable entity.
 *
 * @package Clicalmani\Notification
 * @author clicalmani
 */
final class NotificationContext
{
    /**
     * Initializes a new NotificationContext instance.
     *
     * @param string[] $channels List of requested channel names.
     * @param string $table Database table associated with the notifiable model.
     * @param int|string $id Unique primary key or object identifier of the notifiable instance.
     * @param string $className Fully qualified class name of the notifiable model.
     * @param object $event The notification event instance being dispatched.
     * @param object $notifiable The target entity receiving the notification.
     */
    public function __construct(
        public readonly array $channels,
        public readonly string $table,
        public readonly int|string $id,
        public readonly string $className,
        public readonly object $event,
        public readonly object $notifiable,
    ) {}

    /**
     * Creates a new NotificationContext instance from a notification event and a notifiable model.
     *
     * @param object $event Notification event instance exposing a `via()` method.
     * @param object $notifiable The target model receiving the notification.
     * @return self
     */
    public static function fromEvent(object $event, object $notifiable): self
    {
        $className = $notifiable::class;
        $shortName = basename(str_replace('\\', '/',$className));

        return new self(
            channels:  array_map(
                fn($c) =>$c instanceof BackedEnum ? $c->value : (string) $c,$event->via()
            ),
            table:     method_exists($notifiable, 'getTable')
                            ? $notifiable->getTable()
                            : strtolower($shortName) . 's',
            id:        isset($notifiable->id)
                            ? $notifiable->id
                            : (method_exists($notifiable, 'id') ? $notifiable->id() : spl_object_id($notifiable)),
            className: $className,
            event:     $event,
            notifiable: $notifiable,
        );
    }

    /**
     * Checks if a specific channel is enabled in this notification context.
     *
     * @param string $channel Target channel name.
     * @return bool True if the channel is present, false otherwise.
     */
    public function hasChannel(string $channel): bool
    {
        return in_array($channel,$this->channels, true);
    }

    /**
     * Core routing resolver: retrieves the target delivery route address for a given channel.
     *
     * Queries `routeNotificationFor()` on the target notifiable instance if implemented.
     *
     * @param string $channel Target channel name to query.
     * @return mixed The configured destination address/endpoint or null if unresolved.
     */
    public function routeFor(string $channel): mixed
    {
        if (method_exists($this->notifiable, 'routeNotificationFor')) {
            return $this->notifiable->routeNotificationFor($channel);
        }

        return null;
    }
}