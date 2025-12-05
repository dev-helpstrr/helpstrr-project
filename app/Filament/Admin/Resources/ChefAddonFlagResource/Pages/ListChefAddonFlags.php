<?php

namespace App\Filament\Admin\Resources\ChefAddonFlagResource\Pages;

use App\Filament\Admin\Resources\ChefAddonFlagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChefAddonFlags extends ListRecords
{
    protected static string $resource = ChefAddonFlagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}