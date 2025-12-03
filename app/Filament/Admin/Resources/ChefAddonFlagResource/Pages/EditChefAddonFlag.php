<?php

namespace App\Filament\Admin\Resources\ChefAddonFlagResource\Pages;

use App\Filament\Admin\Resources\ChefAddonFlagResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChefAddonFlag extends EditRecord
{
    protected static string $resource = ChefAddonFlagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}