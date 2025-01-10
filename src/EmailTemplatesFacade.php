<?php

namespace Manuelballmer\EmailTemplates;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Manuelballmer\EmailTemplates\Skeleton\SkeletonClass
 */
class EmailTemplatesFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'email-templates';
    }
}
