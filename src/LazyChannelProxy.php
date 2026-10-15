<?php
namespace Clicalmani\Notification;

use Clicalmani\Notification\Contracts\NotificationChannelInterface;
use RuntimeException;

/**
 * Class LazyChannelProxy
 *
 * Serves as a proxy for custom channels registered via factory closures.
 * Delays the resolution and instantiation of the actual channel until it is first invoked.
 *
 * @package Clicalmani\Notification
 * @author clicalmani
 */
final class LazyChannelProxy implements NotificationChannelInterface
{
    /**
     * Cached instance of the resolved notification channel.
     *
     * @var NotificationChannelInterface|null
     */
    private ?NotificationChannelInterface $resolved = null;

    /**
     * Initializes a new LazyChannelProxy instance.
     *
     * @param string $name Unique name identifier of the extended channel.
     * @param callable(): NotificationChannelInterface $factory Factory closure returning a channel instance.
     */
    public function __construct(
        private string $name,
        private $factory,
    ) {}

    /**
     * Unique name identifier of the channel interface contract.
     *
     * Note: The true channel identity is held in $this->name instance property.
     * The registry indexes extended channels using the explicit key provided at registration.
     *
     * @return string
     */
    public static function name(): string
    {
        return '';
    }

    /**
     * Default static execution priority level.
     *
     * @return int Defaults to 0 prior to dynamic resolution.
     */
    public function priority(): int
    {
        return 0;
    }

    /**
     * Determines whether the proxied channel should execute for the given context.
     * Resolves the channel instance on demand to evaluate the condition.
     *
     * @param NotificationContext $context The notification context instance.
     * @return bool
     */
    public function shouldSend(NotificationContext $context): bool
    {
        return $this->resolve()->shouldSend($context);
    }

    /**
     * Sends the notification by delegating execution to the resolved channel.
     *
     * @param NotificationContext $context The notification context instance.
     * @return void
     */
    public function send(NotificationContext $context): void
    {
        $this->resolve()->send($context);
    }

    /**
     * Dynamically retrieves the priority level from the underlying resolved channel.
     *
     * @return int Priority value configured on the resolved channel class.
     */
    public function priorityValue(): int
    {
        return $this->resolve()::priority();
    }

    /**
     * Resolves and caches the underlying channel instance using the factory closure.
     *
     * @return NotificationChannelInterface The resolved notification channel instance.
     * @throws RuntimeException If the factory closure fails to return a valid NotificationChannelInterface instance.
     */
    private function resolve(): NotificationChannelInterface
    {
        if ($this->resolved === null) {
            $channel = ($this->factory)();

            if (! $channel instanceof NotificationChannelInterface) {
                throw new RuntimeException(
                    sprintf('Extension "%s" must return a NotificationChannelInterface', $this->name)
                );
            }

            $this->resolved = $channel;
        }

        return $this->resolved;
    }
}