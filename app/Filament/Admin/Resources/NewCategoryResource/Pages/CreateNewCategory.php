<?php

namespace App\Filament\Admin\Resources\NewCategoryResource\Pages;

use App\Filament\Admin\Resources\NewCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNewCategory extends CreateRecord
{
    protected static string $resource = NewCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}