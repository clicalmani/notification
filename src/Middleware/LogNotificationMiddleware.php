<?php
namespace Clicalmani\Notification\Middleware;

use Clicalmani\Notification\Contracts\ChannelMiddlewareInterface;
use Clicalmani\Notification\NotificationContext;

/**
 * Class LogNotificationMiddleware
 *
 * Middleware component that logs notification dispatching metadata
 * before passing the context down the middleware execution pipeline.
 *
 * @package Clicalmani\Notification\Middleware
 * @author clicalmani
 */
final class LogNotificationMiddleware implements ChannelMiddlewareInterface
{
    public function handle(NotificationContext $context, callable $next): void
    {
        logger()->info('Notification dispatching', [
            'channel' => static::class,
            'event'   => $context->event::class,
            'table'   => $context->table,
            'id'      => $context->id,
        ]);

        $next($context);
    }
}