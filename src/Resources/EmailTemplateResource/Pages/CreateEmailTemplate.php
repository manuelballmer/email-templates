<?php

namespace Manuelballmer\EmailTemplates\Resources\EmailTemplateResource\Pages;

use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use Manuelballmer\EmailTemplates\Resources\EmailTemplateResource;

class CreateEmailTemplate extends CreateRecord
{
    protected static string $resource = EmailTemplateResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $emailTemplateResource = new EmailTemplateResource();
        $sortedData = $emailTemplateResource->handleLogo($data);

        // Ensure from fields use current team values
        $tenant = \Filament\Facades\Filament::getTenant();
        if ($tenant) {
            if (method_exists($tenant, 'getMailFromAddress')) {
                $sortedData['from']['email'] = $tenant->getMailFromAddress();
            }
            if (method_exists($tenant, 'getMailFromName')) {
                $sortedData['from']['name'] = $tenant->getMailFromName();
            }
        }

        return static::getModel()::create($sortedData);
    }
}
