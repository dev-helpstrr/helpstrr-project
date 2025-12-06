<?php

namespace App\Filament\Admin\Resources\SPManagementResource\Pages;

use App\Filament\Admin\Resources\SPManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSPManagement extends ListRecords
{
    protected static string $resource = SPManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}