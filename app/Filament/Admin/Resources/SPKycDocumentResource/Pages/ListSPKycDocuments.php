<?php

namespace App\Filament\Admin\Resources\SPKycDocumentResource\Pages;

use App\Filament\Admin\Resources\SPKycDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSPKycDocuments extends ListRecords
{
    protected static string $resource = SPKycDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
