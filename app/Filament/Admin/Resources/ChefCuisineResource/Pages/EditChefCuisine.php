<?php

namespace App\Filament\Admin\Resources\ChefCuisineResource\Pages;

use App\Filament\Admin\Resources\ChefCuisineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChefCuisine extends EditRecord
{
    protected static string $resource = ChefCuisineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}