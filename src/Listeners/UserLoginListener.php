<?php

namespace Manuelballmer\EmailTemplates\Listeners;

use Illuminate\Auth\Events\Login;
use Manuelballmer\EmailTemplates\Notifications\UserLoginNotification;

class UserLoginListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     *
     * @return void
     */
    public function handle(Login $event)
    {
        if(config('filament-email-templates.send_emails.login')) {
            $user = $event->user;
            $user->notify(new UserLoginNotification());
        }
    }
}
