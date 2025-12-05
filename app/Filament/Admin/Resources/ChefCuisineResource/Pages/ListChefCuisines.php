<?php

namespace App\Filament\Admin\Resources\ChefCuisineResource\Pages;

use App\Filament\Admin\Resources\ChefCuisineResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChefCuisines extends ListRecords
{
    protected static string $resource = ChefCuisineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}