<?php

namespace App\Filament\Admin\Resources\NewCategoryResource\Pages;

use App\Filament\Admin\Resources\NewCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewNewCategory extends ViewRecord
{
    protected static string $resource = NewCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}