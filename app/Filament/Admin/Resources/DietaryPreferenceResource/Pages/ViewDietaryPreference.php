<?php

namespace App\Filament\Admin\Resources\DietaryPreferenceResource\Pages;

use App\Filament\Admin\Resources\DietaryPreferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDietaryPreference extends ViewRecord
{
    protected static string $resource = DietaryPreferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}