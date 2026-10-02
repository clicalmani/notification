<?php
namespace Clicalmani\Notification\Middleware;

use Clicalmani\Notification\Contracts\ChannelMiddlewareInterface;
use Clicalmani\Notification\NotificationContext;
use Throwable;

/**
 * Class RetryMiddleware
 *
 * Middleware component that automatically retries the delivery of a notification
 * a configured number of times with an exponential backoff delay before rethrowing any exception.
 *
 * @package Clicalmani\Notification\Middleware
 * @author clicalmani
 */
final class RetryMiddleware implements ChannelMiddlewareInterface
{
    public function __construct(private int $attempts = 3) {}

    public function handle(NotificationContext $context, callable $next): void
    {
        $tries = 0;

        beginning:
        try {
            $next($context);
        } catch (Throwable $e) {
            if (++$tries < $this->attempts) {
                usleep(100_000 * $tries);
                goto beginning;
            }
            throw $e;
        }
    }
}