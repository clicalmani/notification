<?php

namespace Notification\Test\Unit;

use PHPUnit\Framework\TestCase;
use Clicalmani\Notification\ChannelRegistry;
use Clicalmani\Notification\Contracts\ChannelMiddlewareInterface;
use Clicalmani\Notification\NotificationContext;
use Notification\Test\Fixtures\Channels\RecordingChannel;

class ChannelRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ChannelRegistry::flush();
    }

    protected function tearDown(): void
    {
        ChannelRegistry::flush();
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────
    // Enregistrement
    // ─────────────────────────────────────────────────────────────

    public function test_register_adds_channel_with_explicit_name(): void
    {
        $channel = new RecordingChannel('mail');

        ChannelRegistry::register($channel, 'mail');

        $this->assertSame($channel, ChannelRegistry::get('mail'));
    }

    public function test_register_returns_channel_when_already_seen(): void
    {
        $a = new RecordingChannel('mail');
        $b = new RecordingChannel('mail');

        ChannelRegistry::register($a, 'mail');
        ChannelRegistry::register($b, 'mail'); // écrase

        $this->assertSame($b, ChannelRegistry::get('mail'));
    }

    public function test_register_many(): void
    {
        ChannelRegistry::registerMany([
            'mail'     => new RecordingChannel('mail'),
            'slack'    => new RecordingChannel('slack'),
            'database' => new RecordingChannel('database'),
        ]);

        $this->assertCount(3, ChannelRegistry::all());
        $this->assertArrayHasKey('mail', ChannelRegistry::all());
        $this->assertArrayHasKey('slack', ChannelRegistry::all());
        $this->assertArrayHasKey('database', ChannelRegistry::all());
    }

    public function test_get_unknown_returns_null(): void
    {
        $this->assertNull(ChannelRegistry::get('missing'));
    }

    public function test_all_returns_every_channel(): void
    {
        ChannelRegistry::register(new RecordingChannel('a'), 'a');
        ChannelRegistry::register(new RecordingChannel('b'), 'b');

        $all = ChannelRegistry::all();

        $this->assertCount(2, $all);
        $this->assertArrayHasKey('a', $all);
        $this->assertArrayHasKey('b', $all);
    }

    // ─────────────────────────────────────────────────────────────
    // Tri par priorité
    // ─────────────────────────────────────────────────────────────

    public function test_sorted_orders_by_descending_priority(): void
    {
        ChannelRegistry::register(new RecordingChannel('low', 10), 'low');
        ChannelRegistry::register(new RecordingChannel('high', 100), 'high');
        ChannelRegistry::register(new RecordingChannel('medium', 50), 'medium');

        $sorted = ChannelRegistry::sorted();
        
        $this->assertSame(['high', 'medium', 'low'], array_keys($sorted));
    }

    public function test_sorted_keeps_insertion_order_on_ties(): void
    {
        ChannelRegistry::register(new RecordingChannel('a', 50), 'a');
        ChannelRegistry::register(new RecordingChannel('b', 50), 'b');
        ChannelRegistry::register(new RecordingChannel('c', 50), 'c');

        $sorted = ChannelRegistry::sorted();

        $this->assertSame(['a', 'b', 'c'], array_keys($sorted));
    }

    // ─────────────────────────────────────────────────────────────
    // Middlewares
    // ─────────────────────────────────────────────────────────────

    public function test_global_middleware_applies_to_all_channels(): void
    {
        $mw = new RecordingMiddleware();

        ChannelRegistry::globalMiddleware($mw);

        $this->assertSame([$mw], ChannelRegistry::middlewareFor('any'));
        $this->assertSame([$mw], ChannelRegistry::middlewareFor('other'));
    }

    public function test_channel_specific_middleware(): void
    {
        $mw = new RecordingMiddleware();

        ChannelRegistry::middleware('mail', $mw);

        $this->assertSame([$mw], ChannelRegistry::middlewareFor('mail'));
        $this->assertSame([], ChannelRegistry::middlewareFor('slack'));
    }

    public function test_global_and_specific_are_merged_with_global_first(): void
    {
        $global   = new RecordingMiddleware('global');
        $specific = new RecordingMiddleware('specific');

        ChannelRegistry::globalMiddleware($global);
        ChannelRegistry::middleware('mail', $specific);

        $result = ChannelRegistry::middlewareFor('mail');

        $this->assertSame([$global, $specific], $result);
    }

    public function test_multiple_global_middlewares_preserve_order(): void
    {
        $a = new RecordingMiddleware('a');
        $b = new RecordingMiddleware('b');

        ChannelRegistry::globalMiddleware($a);
        ChannelRegistry::globalMiddleware($b);

        $this->assertSame([$a, $b], ChannelRegistry::middlewareFor('any'));
    }

    // ─────────────────────────────────────────────────────────────
    // Flush
    // ─────────────────────────────────────────────────────────────

    public function test_flush_clears_channels_and_middlewares(): void
    {
        ChannelRegistry::register(new RecordingChannel('mail'), 'mail');
        ChannelRegistry::globalMiddleware(new RecordingMiddleware());
        ChannelRegistry::middleware('mail', new RecordingMiddleware());

        ChannelRegistry::flush();

        $this->assertEmpty(ChannelRegistry::all());
        $this->assertEmpty(ChannelRegistry::middlewareFor('mail'));
    }
}

// ─── Fixture middleware ──────────────────────────────────────────

class RecordingMiddleware implements ChannelMiddlewareInterface
{
    public function __construct(private string $label = 'mw') {}

    public function handle(NotificationContext $context, callable $next): void
    {
        $next($context);
    }

    public function label(): string
    {
        return $this->label;
    }
}