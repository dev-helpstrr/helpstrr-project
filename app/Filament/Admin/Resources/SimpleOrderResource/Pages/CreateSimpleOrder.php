<?php

namespace App\Filament\Admin\Resources\SimpleOrderResource\Pages;

use App\Filament\Admin\Resources\SimpleOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSimpleOrder extends CreateRecord
{
    protected static string $resource = SimpleOrderResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
