<?php

namespace App\Filament\Admin\Resources\SPKycDocumentResource\Pages;

use App\Filament\Admin\Resources\SPKycDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSPKycDocument extends EditRecord
{
    protected static string $resource = SPKycDocumentResource::class;

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
