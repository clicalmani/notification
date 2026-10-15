<?php

namespace Notification\Test\Fixtures;

class FakeNotification
{
    /** @var string[] */
    public array $channels = ['mail'];

    /** @var array<string, mixed> */
    public array $payload = ['message' => 'Hello'];

    public function via($notifiable = null): array
    {
        return $this->channels;
    }

    public function toArray($notifiable = null): array
    {
        return $this->payload;
    }

    public function toDatabase($notifiable = null): array
    {
        return $this->payload;
    }
}