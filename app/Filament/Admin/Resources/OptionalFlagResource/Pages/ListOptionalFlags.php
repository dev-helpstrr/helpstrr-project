<?php

namespace App\Filament\Admin\Resources\OptionalFlagResource\Pages;

use App\Filament\Admin\Resources\OptionalFlagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOptionalFlags extends ListRecords
{
    protected static string $resource = OptionalFlagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}