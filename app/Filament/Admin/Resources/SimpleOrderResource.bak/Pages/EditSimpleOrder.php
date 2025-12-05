<?php

namespace App\Filament\Admin\Resources\SimpleOrderResource\Pages;

use App\Filament\Admin\Resources\SimpleOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSimpleOrder extends EditRecord
{
    protected static string $resource = SimpleOrderResource::class;

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
