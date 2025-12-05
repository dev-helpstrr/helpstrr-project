<?php

namespace App\Filament\Admin\Resources\NotificationPanelResource\Pages;

use App\Filament\Admin\Resources\NotificationPanelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotificationPanel extends EditRecord
{
    protected static string $resource = NotificationPanelResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
