<?php


namespace Manuelballmer\EmailTemplates\Contracts;

interface TokenReplacementInterface
{
    public function replaceTokens(string $content, $models);
}
