<?php

namespace Notification\Test\Unit;

use PHPUnit\Framework\TestCase;
use Clicalmani\Notification\AnonymousNotifiable;
use Clicalmani\Notification\ChannelRegistry;
use Clicalmani\Notification\NotificationManager;
use Notification\Test\Fixtures\Channels\RecordingChannel;

class NotificationManagerTest extends TestCase
{
    private NotificationManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        ChannelRegistry::flush();
        $this->manager = new NotificationManager();
    }

    protected function tearDown(): void
    {
        ChannelRegistry::flush();
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────
    // channel() — enregistrement
    // ─────────────────────────────────────────────────────────────

    public function test_channel_registers_a_single_channel(): void
    {
        $channel = new RecordingChannel('mail');

        $this->manager->channel([$channel, 'mail']);

        $this->assertSame($channel, ChannelRegistry::get('mail'));
    }

    public function test_channel_returns_manager_for_chaining(): void
    {
        $result = $this->manager->channel([new RecordingChannel('a'), 'a']);

        $this->assertSame($this->manager, $result);
    }

    // ─────────────────────────────────────────────────────────────
    // extend() — canal paresseux
    // ─────────────────────────────────────────────────────────────

    public function test_extend_registers_lazy_channel(): void
    {
        $invoked = 0;

        $this->manager->extend('slack', function () use (&$invoked) {
            $invoked++;
            return new RecordingChannel('slack');
        });

        $this->assertNotNull(ChannelRegistry::get('slack'));
        $this->assertSame(0, $invoked, 'Factory should not be invoked until used.');
    }

    public function test_extend_overrides_existing_channel(): void
    {
        $original = new RecordingChannel('mail');
        ChannelRegistry::register($original, 'mail');

        $this->manager->extend('mail', fn() => new RecordingChannel('mail-v2'));

        $this->assertNotSame($original, ChannelRegistry::get('mail'));
    }

    public function test_extend_returns_manager_for_chaining(): void
    {
        $result = $this->manager->extend('slack', fn() => new RecordingChannel('slack'));

        $this->assertSame($this->manager, $result);
    }

    // ─────────────────────────────────────────────────────────────
    // middleware()
    // ─────────────────────────────────────────────────────────────

    public function test_middleware_attaches_to_specific_channel(): void
    {
        $mw = new class implements \Clicalmani\Notification\Contracts\ChannelMiddlewareInterface {
            public function handle(\Clicalmani\Notification\NotificationContext $c, callable $next): void
            {
                $next($c);
            }
        };

        $this->manager->middleware('mail', $mw);

        $this->assertCount(1, ChannelRegistry::middlewareFor('mail'));
        $this->assertCount(0, ChannelRegistry::middlewareFor('slack'));
    }

    public function test_global_middleware_attaches_to_all(): void
    {
        $mw = new class implements \Clicalmani\Notification\Contracts\ChannelMiddlewareInterface {
            public function handle(\Clicalmani\Notification\NotificationContext $c, callable $next): void
            {
                $next($c);
            }
        };

        $this->manager->globalMiddleware($mw);

        $this->assertCount(1, ChannelRegistry::middlewareFor('mail'));
        $this->assertCount(1, ChannelRegistry::middlewareFor('slack'));
    }

    // ─────────────────────────────────────────────────────────────
    // route() — destinataire ad-hoc
    // ─────────────────────────────────────────────────────────────

    public function test_route_returns_anonymous_notifiable(): void
    {
        $anonymous = $this->manager->route('mail', 'user@example.com');

        $this->assertInstanceOf(AnonymousNotifiable::class, $anonymous);
        $this->assertSame('user@example.com', $anonymous->routeNotificationFor('mail'));
    }

    public function test_route_supports_chaining(): void
    {
        $anonymous = $this->manager
            ->route('mail', 'user@example.com')
            ->route('slack', 'hook');

        $this->assertCount(2, $anonymous->routes());
    }

    // ─────────────────────────────────────────────────────────────
    // channels() / flush()
    // ─────────────────────────────────────────────────────────────

    public function test_channels_lists_registered(): void
    {
        ChannelRegistry::register(new RecordingChannel('mail'), 'mail');
        ChannelRegistry::register(new RecordingChannel('slack'), 'slack');

        $names = $this->manager->channels();

        $this->assertContains('mail', $names);
        $this->assertContains('slack', $names);
    }

    public function test_flush_removes_everything(): void
    {
        ChannelRegistry::register(new RecordingChannel('mail'), 'mail');
        $this->manager->extend('slack', fn() => new RecordingChannel('slack'));

        $this->manager->flush();

        $this->assertEmpty(ChannelRegistry::all());
        $this->assertEmpty($this->manager->channels());
    }
}