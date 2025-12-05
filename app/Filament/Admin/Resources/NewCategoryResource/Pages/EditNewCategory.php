<?php

namespace App\Filament\Admin\Resources\NewCategoryResource\Pages;

use App\Filament\Admin\Resources\NewCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNewCategory extends EditRecord
{
    protected static string $resource = NewCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}