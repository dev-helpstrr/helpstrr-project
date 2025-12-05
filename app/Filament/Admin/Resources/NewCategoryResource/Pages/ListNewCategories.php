<?php

namespace App\Filament\Admin\Resources\NewCategoryResource\Pages;

use App\Filament\Admin\Resources\NewCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNewCategories extends ListRecords
{
    protected static string $resource = NewCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}