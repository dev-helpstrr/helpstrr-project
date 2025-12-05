<?php

namespace App\Filament\Admin\Resources\PayoutResource\Pages;

use App\Filament\Admin\Resources\PayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePayout extends CreateRecord
{
    protected static string $resource = PayoutResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
