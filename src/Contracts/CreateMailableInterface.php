<?php

namespace Manuelballmer\EmailTemplates\Contracts;

interface CreateMailableInterface
{
    public function createMailable($record);
}
