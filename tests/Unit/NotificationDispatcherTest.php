<?php

namespace Notification\Test\Unit;

use Clicalmani\Core\Support\Facades\Event;
use PHPUnit\Framework\TestCase;
use Clicalmani\Notification\Contracts\ChannelMiddlewareInterface;
use Notification\Test\Fixtures\Channels\RecordingChannel;
use Notification\Test\Fixtures\Notifiables\FakeNotifiable;
use Notification\Test\Fixtures\Notifications\FakeNotification;
use Clicalmani\Notification\ {
    ChannelRegistry,
    NotificationContext,
    NotificationDispatcher
};
use Clicalmani\Notification\Events\ {
    NotificationFailed,
    NotificationSending,
    NotificationSent,
    NotificationSkipped
};

class NotificationDispatcherTest extends TestCase
{
    /** @var array<string, array> */
    private array $capturedEvents = [];

    protected function setUp(): void
    {
        parent::setUp();

        ChannelRegistry::flush();
        $this->capturedEvents = [];

        // Capture des événements via un mini-bus
        $this->listenToEvents();
    }

    protected function tearDown(): void
    {
        ChannelRegistry::flush();
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────
    // Envoi de base
    // ─────────────────────────────────────────────────────────────

    public function test_dispatches_to_all_requested_channels(): void
    {
        $mail  = new RecordingChannel('mail');
        $slack = new RecordingChannel('slack');
        $unused = new RecordingChannel('unused');

        ChannelRegistry::register($mail, 'mail');
        ChannelRegistry::register($slack, 'slack');
        ChannelRegistry::register($unused, 'unused');

        $notification = new FakeNotification();
        $notification->channels = ['mail', 'slack'];

        $this->dispatch($notification);

        $this->assertCount(1, $mail->sent);
        $this->assertCount(1, $slack->sent);
        $this->assertCount(0, $unused->sent);
    }

    public function test_skips_channel_not_in_via(): void
    {
        $mail = new RecordingChannel('mail');
        $slack = new RecordingChannel('slack');

        ChannelRegistry::register($mail, 'mail');
        ChannelRegistry::register($slack, 'slack');

        $notification = new FakeNotification();
        $notification->channels = ['mail'];

        $this->dispatch($notification);

        $this->assertCount(1, $mail->sent);
        $this->assertCount(0, $slack->sent);
    }

    // ─────────────────────────────────────────────────────────────
    // Ordre de priorité
    // ─────────────────────────────────────────────────────────────

    public function test_channels_are_executed_in_priority_order(): void
    {
        $order = [];

        $database  = new RecordingChannel('database', priority: 100);
        $mail      = new RecordingChannel('mail',     priority: 50);
        $broadcast = new RecordingChannel('broadcast',priority: 10);

        $database->onSend  = function() use(&$order) {$order[] = 'database';};
        $mail->onSend      = function() use(&$order) {$order[] = 'mail';};
        $broadcast->onSend = function() use(&$order) {$order[] = 'broadcast';};

        ChannelRegistry::register($database, 'database');
        ChannelRegistry::register($mail, 'mail');
        ChannelRegistry::register($broadcast, 'broadcast');

        $notification = new FakeNotification();
        $notification->channels = ['mail', 'broadcast', 'database']; // ordre aléatoire

        $this->dispatch($notification);

        $this->assertSame(['database', 'mail', 'broadcast'], $order);
    }

    // ─────────────────────────────────────────────────────────────
    // Événements de cycle de vie
    // ─────────────────────────────────────────────────────────────

    public function test_sending_event_is_emitted_before_send(): void
    {
        $channel = new RecordingChannel('mail');
        ChannelRegistry::register($channel, 'mail');

        $this->dispatch(new FakeNotification());

        $this->assertEventFired(NotificationSending::class, 1);
    }

    public function test_sent_event_is_emitted_after_success(): void
    {
        $channel = new RecordingChannel('mail');
        ChannelRegistry::register($channel, 'mail');

        $this->dispatch(new FakeNotification());

        $this->assertEventFired(NotificationSent::class, 1);
    }

    public function test_failed_event_is_emitted_on_exception(): void
    {
        $channel = new RecordingChannel('mail');
        $channel->throwOnSend = new \RuntimeException('Boom');

        ChannelRegistry::register($channel, 'mail');

        $this->dispatch(new FakeNotification());

        $this->assertEventFired(NotificationFailed::class, 1);
    }

    public function test_sent_is_not_emitted_when_send_throws(): void
    {
        $channel = new RecordingChannel('mail');
        $channel->throwOnSend = new \RuntimeException('Boom');

        ChannelRegistry::register($channel, 'mail');

        $this->dispatch(new FakeNotification());

        $this->assertEventFired(NotificationSending::class, 1);
        $this->assertEventFired(NotificationFailed::class, 1);
        $this->assertEventFired(NotificationSent::class, 0);
    }

    public function test_skipped_event_is_emitted_when_should_send_false(): void
    {
        $channel = new RecordingChannel('mail');
        $channel->shouldSendResult = false;

        ChannelRegistry::register($channel, 'mail');

        $this->dispatch(new FakeNotification());

        $this->assertEventFired(NotificationSkipped::class, 1);
        $this->assertEventFired(NotificationSending::class, 0);
        $this->assertCount(0, $channel->sent);
    }

    public function test_failure_on_one_channel_does_not_stop_others(): void
    {
        $mail  = new RecordingChannel('mail');
        $slack = new RecordingChannel('slack');

        $mail->throwOnSend = new \RuntimeException('Mail boom');

        ChannelRegistry::register($mail, 'mail');
        ChannelRegistry::register($slack, 'slack');

        $notification = new FakeNotification();
        $notification->channels = ['mail', 'slack'];

        $this->dispatch($notification);

        $this->assertCount(0, $mail->sent);
        $this->assertCount(1, $slack->sent);
        $this->assertEventFired(NotificationFailed::class, 1);
        $this->assertEventFired(NotificationSent::class, 1);
    }

    // ─────────────────────────────────────────────────────────────
    // Middlewares
    // ─────────────────────────────────────────────────────────────

    public function test_global_middleware_wraps_send(): void
    {
        $channel = new RecordingChannel('mail');
        ChannelRegistry::register($channel, 'mail');

        $log = [];

        ChannelRegistry::globalMiddleware(new class($log) implements ChannelMiddlewareInterface {
            public function __construct(private array &$log) {}
            public function handle(NotificationContext $context, callable $next): void
            {
                $this->log[] = 'before';
                $next($context);
                $this->log[] = 'after';
            }
        });

        $this->dispatch(new FakeNotification());

        $this->assertSame(['before', 'after'], $log);
    }

    public function test_middlewares_execute_in_registration_order(): void
    {
        $channel = new RecordingChannel('mail');
        ChannelRegistry::register($channel, 'mail');

        $trace = [];

        ChannelRegistry::globalMiddleware(new class($trace) implements ChannelMiddlewareInterface {
            public function __construct(private array &$trace) {}
            public function handle(NotificationContext $c, callable $next): void
            {
                $this->trace[] = 'mw1';
                $next($c);
            }
        });

        ChannelRegistry::middleware('mail', new class($trace) implements ChannelMiddlewareInterface {
            public function __construct(private array &$trace) {}
            public function handle(NotificationContext $c, callable $next): void
            {
                $this->trace[] = 'mw2';
                $next($c);
            }
        });

        $this->dispatch(new FakeNotification());

        $this->assertSame(['mw1', 'mw2'], $trace);
    }

    public function test_middleware_can_prevent_send(): void
    {
        $channel = new RecordingChannel('mail');
        ChannelRegistry::register($channel, 'mail');

        ChannelRegistry::globalMiddleware(new class implements ChannelMiddlewareInterface {
            public function handle(NotificationContext $c, callable $next): void
            {
                // Ne pas appeler $next → send() jamais exécuté
            }
        });

        $this->dispatch(new FakeNotification());

        $this->assertCount(0, $channel->sent);
        $this->assertEventFired(NotificationSent::class, 1); // send() a "réussi" du point de vue du dispatcher
    }

    public function test_middleware_exception_marks_channel_as_failed(): void
    {
        $channel = new RecordingChannel('mail');
        ChannelRegistry::register($channel, 'mail');

        ChannelRegistry::globalMiddleware(new class implements ChannelMiddlewareInterface {
            public function handle(NotificationContext $c, callable $next): void
            {
                throw new \RuntimeException('Middleware boom');
            }
        });

        $this->dispatch(new FakeNotification());

        $this->assertEventFired(NotificationFailed::class, 1);
        $this->assertEventFired(NotificationSent::class, 0);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    private function dispatch(FakeNotification $notification): void
    {
        (new NotificationDispatcher())->dispatchNow($notification, new FakeNotifiable());
    }

    private function assertEventFired(string $eventClass, int $count): void
    {
        $actual = count($this->capturedEvents[$eventClass] ?? []);
        $this->assertSame(
            $count,
            $actual,
            sprintf('Expected %d %s, got %d.', $count, $eventClass, $actual)
        );
    }

    private function listenToEvents(): void
    {
        $classes = [
            NotificationSending::class,
            NotificationSent::class,
            NotificationFailed::class,
            NotificationSkipped::class,
        ];

        foreach ($classes as $class) {
            Event::listen($class, function ($event) use ($class) {
                $this->capturedEvents[$class][] = $event;
            });
        }
    }
}