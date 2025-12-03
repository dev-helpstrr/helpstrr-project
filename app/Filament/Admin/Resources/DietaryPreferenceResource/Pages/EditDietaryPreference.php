<?php

namespace App\Filament\Admin\Resources\DietaryPreferenceResource\Pages;

use App\Filament\Admin\Resources\DietaryPreferenceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDietaryPreference extends EditRecord
{
    protected static string $resource = DietaryPreferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}