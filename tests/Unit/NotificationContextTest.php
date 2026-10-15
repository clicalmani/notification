<?php

namespace Notification\Test\Unit\Notifications;

use Clicalmani\Notification\NotificationContext;
use PHPUnit\Framework\TestCase;
use Notification\Test\Fixtures\Notifiables\FakeNotifiable;
use Notification\Test\Fixtures\Notifications\FakeNotification;

class NotificationContextTest extends TestCase
{
    public function test_from_event_extracts_notifiable_identity(): void
    {
        $notifiable   = new FakeNotifiable();
        $notification = new FakeNotification();

        $context = NotificationContext::fromEvent($notification, $notifiable);

        $this->assertSame('fake_notifiables', $context->table);
        $this->assertSame(1, $context->id);
        $this->assertSame(FakeNotifiable::class, $context->className);
        $this->assertSame($notifiable, $context->notifiable);
        $this->assertSame($notification, $context->event);
    }

    public function test_from_event_normalizes_channels(): void
    {
        $notifiable   = new FakeNotifiable();
        $notification = new FakeNotification();
        $notification->channels = ['mail', 'database'];

        $context = NotificationContext::fromEvent($notification, $notifiable);

        $this->assertSame(['mail', 'database'], $context->channels);
    }

    public function test_from_event_accepts_backed_enums_as_channels(): void
    {
        $notifiable = new FakeNotifiable();

        $notification = new class {
            public function via($n = null): array
            {
                return [FakeChannel::MAIL, FakeChannel::DATABASE];
            }
        };

        $context = NotificationContext::fromEvent($notification, $notifiable);

        $this->assertSame(['mail', 'database'], $context->channels);
    }

    public function test_has_channel_returns_true_when_present(): void
    {
        $notifiable   = new FakeNotifiable();
        $notification = new FakeNotification();
        $notification->channels = ['mail', 'database'];

        $context = NotificationContext::fromEvent($notification, $notifiable);

        $this->assertTrue($context->hasChannel('mail'));
        $this->assertTrue($context->hasChannel('database'));
        $this->assertFalse($context->hasChannel('slack'));
    }

    public function test_route_for_delegates_to_notifiable(): void
    {
        $notifiable = new FakeNotifiable();
        $notifiable->routes = ['mail' => 'user@example.com'];

        $context = NotificationContext::fromEvent(
            new FakeNotification(),
            $notifiable
        );

        $this->assertSame('user@example.com', $context->routeFor('mail'));
        $this->assertNull($context->routeFor('slack'));
    }

    public function test_route_for_returns_null_when_method_missing(): void
    {
        $notifiable = new class { public int $id = 1; };

        $context = NotificationContext::fromEvent(
            new FakeNotification(),
            $notifiable
        );

        $this->assertNull($context->routeFor('mail'));
    }

    public function test_from_event_falls_back_to_object_id_when_no_id(): void
    {
        $notifiable = new class {
            public function getTable(): string { return 'x'; }
        };

        $context = NotificationContext::fromEvent(
            new FakeNotification(),
            $notifiable
        );

        $this->assertNotNull($context->id);
    }

    public function test_from_event_derives_table_name_when_missing(): void
    {
        $notifiable = new class { public int $id = 1; };

        $context = NotificationContext::fromEvent(
            new FakeNotification(),
            $notifiable
        );

        // "class@anonymous" → contient "anonymous", on ne teste pas la valeur exacte
        $this->assertNotEmpty($context->table);
    }
}

// ─── Enum fixture ────────────────────────────────────────────────

enum FakeChannel: string
{
    case MAIL     = 'mail';
    case DATABASE = 'database';
}