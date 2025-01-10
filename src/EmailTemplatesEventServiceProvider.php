<?php

namespace Manuelballmer\EmailTemplates;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Manuelballmer\EmailTemplates\Listeners\PasswordResetListener;
use Manuelballmer\EmailTemplates\Listeners\UserLockoutListener;
use Manuelballmer\EmailTemplates\Listeners\UserLoginListener;
use Manuelballmer\EmailTemplates\Listeners\UserRegisteredListener;
use Manuelballmer\EmailTemplates\Listeners\UserVerifiedListener;

class EmailTemplatesEventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the package.
     *
     * @var array
     */
    protected $listen = [
        Login::class => [
            UserLoginListener::class,
        ],
        Registered::class => [
            UserRegisteredListener::class,
        ],
        PasswordReset::class => [
            PasswordResetListener::class,
        ],
        Lockout::class => [
            UserLockoutListener::class,
        ],
        Verified::class => [
            UserVerifiedListener::class,
        ],
    ];

    /**
     * Register any other events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }

    protected function configureEmailVerification() {}
}
