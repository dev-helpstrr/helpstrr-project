<?php

namespace App\Filament\Admin\Resources\OptionalFlagResource\Pages;

use App\Filament\Admin\Resources\OptionalFlagResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOptionalFlag extends ViewRecord
{
    protected static string $resource = OptionalFlagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}