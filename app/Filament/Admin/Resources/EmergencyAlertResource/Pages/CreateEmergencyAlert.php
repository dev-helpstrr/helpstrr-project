<?php

namespace App\Filament\Admin\Resources\EmergencyAlertResource\Pages;

use App\Filament\Admin\Resources\EmergencyAlertResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEmergencyAlert extends CreateRecord
{
    protected static string $resource = EmergencyAlertResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
