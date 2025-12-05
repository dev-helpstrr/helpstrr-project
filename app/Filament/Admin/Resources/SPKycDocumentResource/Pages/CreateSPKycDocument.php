<?php

namespace App\Filament\Admin\Resources\SPKycDocumentResource\Pages;

use App\Filament\Admin\Resources\SPKycDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSPKycDocument extends CreateRecord
{
    protected static string $resource = SPKycDocumentResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
