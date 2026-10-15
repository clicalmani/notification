<?php

namespace Clicalmani\Notification\Channels;

use Clicalmani\Notification\Concerns\DefaultChannelBehavior;
use Clicalmani\Notification\Contracts\NotificationChannelInterface;
use Clicalmani\Notification\NotificationContext;
use RuntimeException;

final class EmailChannel implements NotificationChannelInterface
{
    use DefaultChannelBehavior;

    public static function name(): string
    {
        return 'mail'; 
    }

    public function priority(): int
    {
        return 50;
    }

    public function shouldSend(NotificationContext $context): bool
    {
        return $context->routeFor(self::name()) !== null
            && method_exists($context->event, 'toMail');
    }

    public function send(NotificationContext $context): void
    {
        $to = $context->routeFor(self::name());

        if (! $to) {
            throw new RuntimeException('No email route resolved.');
        }

        mailer()->to($to)->send($context->event->toMail());
    }
}