<?php

namespace Notification\Test\Fixtures\Channels;

use Clicalmani\Notification\Contracts\NotificationChannelInterface;
use Clicalmani\Notification\NotificationContext;

class RecordingChannel implements NotificationChannelInterface
{
    /** @var NotificationContext[] */
    public array $sent = [];

    public int $shouldSendCalls = 0;

    public bool $shouldSendResult = true;
    public ?\Throwable $throwOnSend = null;
    public ?\Closure $onSend = null;

    public function __construct(
        private string $name,
        private int $priority = 0,
    ) {}

    /**
     * Interface impose name() static. On expose l'identité via getName().
     */
    public static function name(): string
    {
        return '';
    }

    public function priority(): int
    {
        return $this->priority;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function shouldSend(NotificationContext $context): bool
    {
        $this->shouldSendCalls++;

        return $this->shouldSendResult;
    }

    public function send(NotificationContext $context): void
    {
        if ($this->onSend) {
            ($this->onSend)($context);
        }

        if ($this->throwOnSend) {
            throw $this->throwOnSend;
        }

        $this->sent[] = $context;
    }
}