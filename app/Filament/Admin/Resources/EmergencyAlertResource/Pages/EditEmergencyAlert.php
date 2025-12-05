<?php

namespace App\Filament\Admin\Resources\EmergencyAlertResource\Pages;

use App\Filament\Admin\Resources\EmergencyAlertResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmergencyAlert extends EditRecord
{
    protected static string $resource = EmergencyAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
