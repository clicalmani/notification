<?php

namespace Notification\Test\Unit;

use Clicalmani\Notification\AnonymousNotifiable;
use PHPUnit\Framework\TestCase;

class AnonymousNotifiableTest extends TestCase
{
    public function test_route_stores_channel_and_value(): void
    {
        $anonymous = new AnonymousNotifiable();

        $anonymous->route('mail', 'user@example.com');

        $this->assertSame('user@example.com', $anonymous->routeNotificationFor('mail'));
    }

    public function test_route_supports_fluent_chaining(): void
    {
        $anonymous = new AnonymousNotifiable();

        $anonymous
            ->route('mail', 'user@example.com')
            ->route('slack', 'https://hooks.slack.com/x')
            ->route('sms', '+22990000000');

        $this->assertCount(3, $anonymous->routes());
        $this->assertSame('user@example.com', $anonymous->routeNotificationFor('mail'));
        $this->assertSame('https://hooks.slack.com/x', $anonymous->routeNotificationFor('slack'));
    }

    public function test_route_notification_for_unknown_channel_returns_null(): void
    {
        $anonymous = new AnonymousNotifiable();
        $anonymous->route('mail', 'user@example.com');

        $this->assertNull($anonymous->routeNotificationFor('slack'));
    }

    public function test_route_overwrites_previous_value(): void
    {
        $anonymous = new AnonymousNotifiable();

        $anonymous->route('mail', 'old@example.com');
        $anonymous->route('mail', 'new@example.com');

        $this->assertSame('new@example.com', $anonymous->routeNotificationFor('mail'));
    }

    public function test_routes_returns_all_channels(): void
    {
        $anonymous = new AnonymousNotifiable();
        $anonymous->route('mail', 'a@b.c');
        $anonymous->route('slack', 'hook');

        $routes = $anonymous->routes();

        $this->assertSame([
            'mail'  => 'a@b.c',
            'slack' => 'hook',
        ], $routes);
    }

    public function test_id_is_stable_for_same_routes(): void
    {
        $a = new AnonymousNotifiable();
        $a->route('mail', 'user@example.com');

        $b = new AnonymousNotifiable();
        $b->route('mail', 'user@example.com');

        $this->assertSame($a->id(), $b->id());
    }

    public function test_id_changes_for_different_routes(): void
    {
        $a = new AnonymousNotifiable();
        $a->route('mail', 'a@example.com');

        $b = new AnonymousNotifiable();
        $b->route('mail', 'b@example.com');

        $this->assertNotSame($a->id(), $b->id());
    }
}