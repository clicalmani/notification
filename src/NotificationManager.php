<?php
namespace Clicalmani\Notification;

use Clicalmani\Notification\Contracts\ChannelMiddlewareInterface;
use Clicalmani\Notification\Contracts\NotificationChannelInterface;
use RuntimeException;

/**
 * Class NotificationManager
 *
 * Primary entry point for configuring and interacting with the notification system.
 * Provides a fluent API to register channels, attach middleware pipelines,
 * register custom channel factories, set up ad-hoc routing, and dispatch notifications.
 *
 * @package Clicalmani\Notification
 * @author clicalmani
 */
final class NotificationManager
{
    /**
     * Custom channels registered dynamically via extend() — resolved on demand via factories.
     *
     * @var array<string, callable(): NotificationChannelInterface>
     */
    private array $extensions = [];

    /**
     * Initializes a new NotificationManager instance.
     *
     * @param ChannelRegistry $registry Channel registry instance.
     * @param NotificationDispatcher $dispatcher Notification dispatcher instance.
     */
    public function __construct(
        private ChannelRegistry $registry = new ChannelRegistry(),
        private NotificationDispatcher $dispatcher = new NotificationDispatcher(),
    ) {}

    /**
     * Registers one or multiple notification channels.
     *
     * @param string|array<int|string, string|NotificationChannelInterface> $channels Single channel or list of channels.
     * @return self
     */
    public function channel(string|array $channels): self
    {
        foreach ((array) $channels as $channel) {
            $this->registry::register($channel);
        }

        return $this;
    }

    /**
     * Attaches a middleware to a specific channel.
     *
     * @param string $channel Target channel name.
     * @param ChannelMiddlewareInterface|string $mw Middleware instance or class name.
     * @return self
     */
    public function middleware(string $channel, ChannelMiddlewareInterface|string $mw): self
    {
        $this->registry::middleware($channel, $mw);

        return $this;
    }

    /**
     * Attaches a global middleware applied across all channels.
     *
     * @param ChannelMiddlewareInterface|string $mw Middleware instance or class name.
     * @return self
     */
    public function globalMiddleware(ChannelMiddlewareInterface|string $mw): self
    {
        $this->registry::globalMiddleware($mw);

        return $this;
    }

    /**
     * Overrides an existing channel OR registers a custom channel factory closure.
     *
     * Case 1 — Replace an existing channel:
     *   Notification::extend('email', fn() => new MyEmailChannel);
     *
     * Case 2 — Register a new custom channel:
     *   Notification::extend('slack', fn() => new SlackChannel($token));
     *
     * @param string $name Unique channel identifier name.
     * @param callable(): NotificationChannelInterface $factory Resolver closure.
     * @return self
     */
    public function extend(string $name, callable $factory): self
    {
        $this->extensions[$name] = $factory;

        // Lazily registers a proxy component that resolves on demand.
        $this->registry::register(new LazyChannelProxy($name, $factory));

        return $this;
    }

    /**
     * Creates an ad-hoc recipient route for a specific channel and destination address.
     *
     * Example usage:
     *   Notification::route('mail', 'john@doe.com')->notify(new InvoicePaid);
     *   Notification::route('slack', '#alerts')->route('sms', '+229...')->notify($event);
     *
     * @param string $channel Target channel name.
     * @param mixed $route Delivery address or endpoint target.
     * @return AnonymousNotifiable
     */
    public function route(string $channel, mixed $route): AnonymousNotifiable
    {
        return (new AnonymousNotifiable())->route($channel, $route);
    }

    /**
     * Dispatches a notification event to one or multiple target entities.
     *
     * @param object|array<int, object> $notifiables Target recipient entity or array of entities.
     * @param object $event The notification event instance.
     * @return void
     */
    public function send(object|array $notifiables, object $event): void
    {
        $notifiables = is_object($notifiables) ? [$notifiables] : $notifiables;

        foreach ($notifiables as $notifiable) {
            $this->dispatcher->dispatch($event, $notifiable);
        }
    }

    /**
     * Dispatches a notification event immediately without queuing.
     *
     * @param object $event The notification event instance.
     * @param object $notifiable The target recipient entity.
     * @return void
     */
    public function sendNow(object $event, object $notifiable): void
    {
        $this->dispatcher->dispatchNow($event, $notifiable);
    }

    /**
     * Schedules a notification event for delayed dispatching.
     *
     * @param object $event The notification event instance.
     * @param object $notifiable The target recipient entity.
     * @param int $delay Delay in seconds before execution (defaults to 0).
     * @return void
     */
    public function sendLater(object $event, object $notifiable, int $delay = 0): void
    {
        $this->dispatcher->dispatchLater($event, $notifiable, $delay);
    }

    /**
     * Direct access to the channel registry instance (advanced / testing).
     *
     * @return ChannelRegistry
     */
    public function registry(): ChannelRegistry
    {
        return $this->registry;
    }

    /**
     * Resets and flushes all registered extensions and registry data (useful in testing).
     *
     * @return self
     */
    public function flush(): self
    {
        $this->extensions = [];
        $this->registry::flush();

        return $this;
    }

    /**
     * Returns a list of all currently registered channel names.
     *
     * @return string[]
     */
    public function channels(): array
    {
        return array_keys($this->registry::all());
    }
}