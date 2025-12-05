<?php

namespace App\Filament\Admin\Resources\SimpleOrderResource\Pages;

use App\Filament\Admin\Resources\SimpleOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSimpleOrders extends ListRecords
{
    protected static string $resource = SimpleOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action since orders come from API
        ];
    }
}
