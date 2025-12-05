<?php

namespace App\Filament\Admin\Resources\NotificationPanelResource\Pages;

use App\Filament\Admin\Resources\NotificationPanelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotificationPanels extends ListRecords
{
    protected static string $resource = NotificationPanelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
