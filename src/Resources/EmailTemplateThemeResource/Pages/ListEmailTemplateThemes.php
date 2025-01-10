<?php

namespace Manuelballmer\EmailTemplates\Resources\EmailTemplateThemeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Manuelballmer\EmailTemplates\Resources\EmailTemplateThemeResource;

class ListEmailTemplateThemes extends ListRecords
{
    protected static string $resource = EmailTemplateThemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
