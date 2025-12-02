<?php

namespace App\Filament\Admin\Resources\EmergencyAlertResource\Pages;

use App\Filament\Admin\Resources\EmergencyAlertResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmergencyAlerts extends ListRecords
{
    protected static string $resource = EmergencyAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
