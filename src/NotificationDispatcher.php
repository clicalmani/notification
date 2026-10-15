<?php

namespace Clicalmani\Notification;

use Clicalmani\Notification\Events\{
    NotificationSending,
    NotificationSent,
    NotificationFailed,
    NotificationSkipped,
};
use Clicalmani\Queue\NotificationJob;
use Clicalmani\Notification\Support\Facades\Queue;
use Clicalmani\Queue\Contracts\ShouldQueue;
use Clicalmani\Queue\Events\JobPushed;
use Throwable;

/**
 * Class NotificationDispatcher
 *
 * Dispatches notification events across all requested and registered channels,
 * manages immediate and queued execution, handles lifecycle events (Sending, Sent, Skipped, Failed),
 * and executes channel middleware pipelines.
 *
 * @package Clicalmani\Notification
 * @author clicalmani
 */
final class NotificationDispatcher
{
    /**
     * Dispatches a notification event to a specified target entity across its enabled channels.
     *
     * @param object $event The notification event instance.
     * @param object $notifiable The target entity receiving the notification.
     * @return void
     */
    public function dispatch(object $event, object $notifiable): void
    {
        // If the notification should be queued, push the job to the queue worker.
        if ($event instanceof ShouldQueue) {
            $this->enqueue($event, $notifiable);
            return;
        }

        // Otherwise, process synchronous execution.
        $this->dispatchNow($event, $notifiable);
    }

    /**
     * Dispatches a notification event immediately without queuing.
     *
     * @param object $event The notification event instance.
     * @param object $notifiable The target entity receiving the notification.
     * @return void
     */
    public function dispatchNow(object $event, object $notifiable): void
    {
        $context = NotificationContext::fromEvent($event, $notifiable);

        // If the recipient is an AnonymousNotifiable, filter channels based on configured routes.
        $availableRoutes = method_exists($notifiable, 'routes')
            ? array_keys($notifiable->routes())
            : null;
        
        foreach (ChannelRegistry::sorted() as $channelName =>$channel) {
            if (! $context->hasChannel($channelName)) {
                continue;
            }

            // Route filter: do not dispatch to unrouted channels.
            if ($availableRoutes !== null && ! in_array($channelName,$availableRoutes, true)) {
                continue;
            }

            if (! $channel->shouldSend($context)) {
                event(new NotificationSkipped($channelName,$context));
                continue;
            }

            event(new NotificationSending($channelName,$context));

            try {
                $this->runPipeline($channelName, $context, fn($ctx) => $channel->send($ctx));
                event(new NotificationSent($channelName,$context));
            } catch (Throwable $e) {
                event(new NotificationFailed($channelName, $context,$e));
            }
        }
    }

    /**
     * Enqueues a notification job for asynchronous processing.
     *
     * @param object $event The notification event instance.
     * @param object $notifiable The target entity receiving the notification.
     * @param int $delay Optional execution delay in seconds (defaults to 0).
     * @return void
     */
    private function enqueue(object $event, object $notifiable, int $delay = 0): void
    {
        $job = new NotificationJob($event, $notifiable, [
            'delay' => $delay,
        ]);

        $id = Queue::push($job,$delay);

        event(new JobPushed($id, $job->queue(),$event, $notifiable));
    }

    /**
     * Dispatches a notification for delayed execution — utilized by `notifyLater()`.
     *
     * @param object $event The notification event instance.
     * @param object $notifiable The target entity receiving the notification.
     * @param int $delay Delay in seconds before processing the notification.
     * @return void
     */
    public function dispatchLater(object $event, object $notifiable, int $delay): void
    {
        $this->enqueue($event, $notifiable, $delay);
    }

    /**
     * Runs the notification context through the configured middleware pipeline for a given channel.
     *
     * @param string $channelName Target channel name.
     * @param NotificationContext $context Current notification context instance.
     * @param callable $destination Final callback execution (channel send method).
     * @return void
     */
    private function runPipeline(string $channelName, NotificationContext $context, callable$destination): void
    {
        $middleware = array_reverse(ChannelRegistry::middlewareFor($channelName));

        $pipeline = array_reduce($middleware,
            fn(callable $next, $mw) => fn(NotificationContext$ctx) => $mw->handle($ctx, $next),$destination
        );

        $pipeline($context);
    }
}