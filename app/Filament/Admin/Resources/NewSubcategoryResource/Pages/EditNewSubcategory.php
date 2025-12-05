<?php

namespace App\Filament\Admin\Resources\NewSubcategoryResource\Pages;

use App\Filament\Admin\Resources\NewSubcategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNewSubcategory extends EditRecord
{
    protected static string $resource = NewSubcategoryResource::class;

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