<?php

namespace App\Filament\Admin\Resources\ServiceCategoryResource\Pages;

use App\Filament\Admin\Resources\ServiceCategoryResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListServiceCategories extends ListRecords
{
    protected static string $resource = ServiceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
