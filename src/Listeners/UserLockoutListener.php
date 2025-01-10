<?php

namespace Manuelballmer\EmailTemplates\Listeners;

use Illuminate\Auth\Events\Login;
use Manuelballmer\EmailTemplates\Notifications\UserLockoutNotification;

class UserLockoutListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(Login $event)
    {
        if(config('filament-email-templates.send_emails.locked_out')) {
            $user = $event->user;
            $user->notify(new UserLockoutNotification());
        }

    }
}
