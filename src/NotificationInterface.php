<?php
namespace Clicalmani\Notification;

/**
 * Interface NotificationInterface
 *
 * Defines the contract that all notification classes must implement to specify
 * the delivery channels through which the notification should be sent.
 *
 * @package Clicalmani\Notification
 * @author clicalmani
 */
interface NotificationInterface
{
    /**
     * Get the notification channels the event should be sent on.
     *
     * @return array<int, string|\BackedEnum> List of channel identifiers.
     */
    public function via() : array;
}