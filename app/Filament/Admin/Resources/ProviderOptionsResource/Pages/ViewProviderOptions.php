<?php

namespace App\Filament\Admin\Resources\ProviderOptionsResource\Pages;

use App\Filament\Admin\Resources\ProviderOptionsResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProviderOptions extends ViewRecord
{
    protected static string $resource = ProviderOptionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}