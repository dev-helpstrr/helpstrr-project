<?php

namespace App\Filament\Admin\Resources\NewSubcategoryResource\Pages;

use App\Filament\Admin\Resources\NewSubcategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewNewSubcategory extends ViewRecord
{
    protected static string $resource = NewSubcategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}