<?php

namespace Notification\Test\Unit;

use PHPUnit\Framework\TestCase;
use Clicalmani\Notification\LazyChannelProxy;
use Clicalmani\Notification\NotificationContext;
use Notification\Test\Fixtures\Channels\RecordingChannel;

class LazyChannelProxyTest extends TestCase
{
    public function test_factory_is_not_invoked_until_needed(): void
    {
        $invoked = 0;

        $proxy = new LazyChannelProxy('mail', function () use (&$invoked) {
            $invoked++;
            return new RecordingChannel('mail');
        });

        $this->assertSame(0, $invoked);
    }

    public function test_factory_is_invoked_once_on_first_send(): void
    {
        $invoked = 0;

        $proxy = new LazyChannelProxy('mail', function () use (&$invoked) {
            $invoked++;
            return new RecordingChannel('mail');
        });

        $context = new NotificationContext(
            channels: ['mail'],
            table: 'users',
            id: 1,
            className: 'User',
            event: new \stdClass(),
            notifiable: new \stdClass(),
        );

        $proxy->send($context);
        $proxy->send($context);
        $proxy->send($context);

        $this->assertSame(1, $invoked);
    }

    public function test_should_send_delegates_to_resolved_channel(): void
    {
        $channel = new RecordingChannel('mail');
        $channel->shouldSendResult = false;

        $proxy = new LazyChannelProxy('mail', fn() => $channel);

        $context = new NotificationContext(
            channels: ['mail'],
            table: 'users',
            id: 1,
            className: 'User',
            event: new \stdClass(),
            notifiable: new \stdClass(),
        );

        $this->assertFalse($proxy->shouldSend($context));
        $this->assertSame(1, $channel->shouldSendCalls);
    }

    public function test_invalid_factory_result_throws(): void
    {
        $proxy = new LazyChannelProxy('mail', fn() => new \stdClass());

        $context = new NotificationContext(
            channels: ['mail'],
            table: 'users',
            id: 1,
            className: 'User',
            event: new \stdClass(),
            notifiable: new \stdClass(),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must return a NotificationChannelInterface');

        $proxy->send($context);
    }

    public function test_name_and_priority_are_empty_static(): void
    {
        $proxy = new LazyChannelProxy('mail', fn() => new RecordingChannel('mail'));

        $this->assertSame('', LazyChannelProxy::name());
        $this->assertSame(0, $proxy->priority());
    }
}