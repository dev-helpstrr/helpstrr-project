<?php

namespace App\Filament\Admin\Resources\DietaryPreferenceResource\Pages;

use App\Filament\Admin\Resources\DietaryPreferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDietaryPreferences extends ListRecords
{
    protected static string $resource = DietaryPreferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}