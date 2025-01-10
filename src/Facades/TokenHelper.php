<?php

namespace Manuelballmer\EmailTemplates\Facades;

use Illuminate\Support\Facades\Facade;

class TokenHelper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Manuelballmer\EmailTemplates\Contracts\TokenReplacementInterface::class;
    }

    public static function replace(string $content, $models): string
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor())->replaceTokens($content, $models);
    }
}
