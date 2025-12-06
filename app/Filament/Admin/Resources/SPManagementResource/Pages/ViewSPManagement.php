<?php

namespace App\Filament\Admin\Resources\SPManagementResource\Pages;

use App\Filament\Admin\Resources\SPManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSPManagement extends ViewRecord
{
    protected static string $resource = SPManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}