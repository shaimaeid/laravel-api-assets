<?php

namespace App\Traits;

use Illuminate\Support\Facades\Notification;

trait Notifiable
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notification
     * @return void
     */
    public function notify($notification)
    {
        Notification::send($this, $notification);
    }

    /**
     * Route notifications for the mail channel.
     *
     * @return string
     */
    public function routeNotificationForMail()
    {
        return $this->email;
    }

    /**
     * Route notifications for the SMS channel.
     *
     * @return string
     */
    public function routeNotificationForSms()
    {
        return $this->phone_number;
    }
}
