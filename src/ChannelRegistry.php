<?php
namespace Clicalmani\Notification;

use Clicalmani\Notification\Contracts\NotificationChannelInterface;
use Clicalmani\Notification\Contracts\ChannelMiddlewareInterface;
use RuntimeException;

/**
 * Class ChannelRegistry
 *
 * Central registry for managing notification channel instances, sorting priority,
 * and attaching global or channel-specific middleware pipelines.
 *
 * @package Clicalmani\Notification
 * @author clicalmani
 */
final class ChannelRegistry
{
    /** @var array<string, NotificationChannelInterface> */
    private static array $channels = [];

    /** @var array<string, ChannelMiddlewareInterface[]> */
    private static array $middleware = [];

    /** @var ChannelMiddlewareInterface[] */
    private static array $globalMiddleware = [];

    /**
     * Registers a notification channel.
     * If $name is provided, it overrides $instance::name().
     *
     * @param string|NotificationChannelInterface $channel Channel class name or instance.
     * @param string|null $name Optional explicit channel identifier name.
     * @return void
     * @throws RuntimeException If the channel class does not implement NotificationChannelInterface or lacks a name.
     */
    public static function register(
        string|NotificationChannelInterface $channel,
        ?string $name = null
    ): void {
        $instance = is_string($channel)
            ? new $channel
            : $channel;

        if (! $instance instanceof NotificationChannelInterface) {
            throw new RuntimeException(
                sprintf('%s must implement %s', $instance::class, NotificationChannelInterface::class)
            );
        }

        $key = $name ?? $instance::name();

        if ($key === '') {
            throw new RuntimeException(
                'Cannot register a channel without a name. Pass $name explicitly for proxies.'
            );
        }

        self::$channels[$key] = $instance;
    }

    /**
     * Registers multiple notification channels at once.
     *
     * @param array<int|string, string|NotificationChannelInterface> $channels List of channel class names or instances.
     * @return void
     */
    public static function registerMany(array $channels): void
    {
        foreach ($channels as $channel) {
            self::register($channel);
        }
    }

    /**
     * Attaches middleware to a specific notification channel.
     *
     * @param string $channelName Target channel name.
     * @param string|ChannelMiddlewareInterface $middleware Middleware class name or instance.
     * @return void
     */
    public static function middleware(string $channelName, $middleware): void
    {
        $instance = is_string($middleware)
            ? new $middleware
            : $middleware;

        self::$middleware[$channelName][] = $instance;
    }

    /**
     * Attaches middleware globally to all notification channels.
     *
     * @param string|ChannelMiddlewareInterface $middleware Middleware class name or instance.
     * @return void
     */
    public static function globalMiddleware($middleware): void
    {
        self::$globalMiddleware[] = is_string($middleware)
            ? new $middleware
            : $middleware;
    }

    /**
     * Retrieves a registered notification channel by name.
     *
     * @param string $name Target channel name.
     * @return NotificationChannelInterface|null The channel instance, or null if not registered.
     */
    public static function get(string $name): ?NotificationChannelInterface
    {
        return self::$channels[$name] ?? null;
    }

    /**
     * @return array<string, NotificationChannelInterface>  [name => channel]
     */
    public static function all(): array
    {
        return self::$channels;
    }

    /**
     * Returns registered notification channels sorted by descending priority.
     *
     * @return array<string, NotificationChannelInterface> Sorted map of channel names to channel instances.
     */
    public static function sorted(): array
    {
        $channels = self::$channels;

        uasort($channels, function ($a, $b) {
            $pa = $a instanceof LazyChannelProxy ? $a->priorityValue() : $a::priority();
            $pb = $b instanceof LazyChannelProxy ? $b->priorityValue() : $b::priority();

            return $pb <=> $pa;
        });

        return $channels;
    }

    /**
     * @return ChannelMiddlewareInterface[]
     */
    public static function middlewareFor(string $channelName): array
    {
        return array_merge(
            self::$globalMiddleware,
            self::$middleware[$channelName] ?? []
        );
    }

    /**
     * Flushes and resets all registered channels and middleware pipelines.
     *
     * @return void
     */
    public static function flush(): void
    {
        self::$channels         = [];
        self::$middleware       = [];
        self::$globalMiddleware = [];
    }
}