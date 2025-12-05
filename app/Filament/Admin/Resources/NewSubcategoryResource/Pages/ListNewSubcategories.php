<?php

namespace App\Filament\Admin\Resources\NewSubcategoryResource\Pages;

use App\Filament\Admin\Resources\NewSubcategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNewSubcategories extends ListRecords
{
    protected static string $resource = NewSubcategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}