<?php

namespace App\Filament\Admin\Resources\ProviderOptionsResource\Pages;

use App\Filament\Admin\Resources\ProviderOptionsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProviderOptions extends EditRecord
{
    protected static string $resource = ProviderOptionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}