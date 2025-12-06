<?php

namespace App\Filament\Admin\Resources\SPManagementResource\Pages;

use App\Filament\Admin\Resources\SPManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSPManagement extends EditRecord
{
    protected static string $resource = SPManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}