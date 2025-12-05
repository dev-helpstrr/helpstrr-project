<?php

namespace App\Filament\Admin\Resources\OptionalFlagResource\Pages;

use App\Filament\Admin\Resources\OptionalFlagResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOptionalFlag extends EditRecord
{
    protected static string $resource = OptionalFlagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}