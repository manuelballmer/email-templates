<?php

namespace Manuelballmer\EmailTemplates\Contracts;

interface FormHelperInterface
{
    public function getLanguageOptions();

    public function getTemplateViewOptions();

    public function getRecipientOptions();
}
