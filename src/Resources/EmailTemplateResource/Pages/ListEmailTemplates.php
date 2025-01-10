<?php

namespace Manuelballmer\EmailTemplates\Resources\EmailTemplateResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Manuelballmer\EmailTemplates\Resources\EmailTemplateResource;

class ListEmailTemplates extends ListRecords
{
    protected static string $resource = EmailTemplateResource::class;
    protected $createMailableHelper;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
