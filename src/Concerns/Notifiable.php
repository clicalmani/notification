<?php
namespace Clicalmani\Notification\Concerns;

use Clicalmani\Notification\NotificationDispatcher;

trait Notifiable
{
    public function notify(object $event): void
    {
        (new NotificationDispatcher())->dispatch($event, $this);
    }

    /**
     * Envoi synchrone forcé — ignore ShouldQueue.
     */
    public function notifyNow(object $event): void
    {
        (new NotificationDispatcher())->dispatchNow($event, $this);
    }

    /**
     * Envoi différé.
     */
    public function notifyLater(object $event, int $delay = 0): void
    {
        (new NotificationDispatcher())->dispatchLater($event, $this, $delay);
    }
}