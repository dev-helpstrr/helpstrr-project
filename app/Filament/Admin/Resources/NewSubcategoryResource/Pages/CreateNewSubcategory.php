<?php

namespace App\Filament\Admin\Resources\NewSubcategoryResource\Pages;

use App\Filament\Admin\Resources\NewSubcategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNewSubcategory extends CreateRecord
{
    protected static string $resource = NewSubcategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}