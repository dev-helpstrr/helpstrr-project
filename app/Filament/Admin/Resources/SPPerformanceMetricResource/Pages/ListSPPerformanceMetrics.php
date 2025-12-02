<?php

namespace App\Filament\Admin\Resources\SPPerformanceMetricResource\Pages;

use App\Filament\Admin\Resources\SPPerformanceMetricResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSPPerformanceMetrics extends ListRecords
{
    protected static string $resource = SPPerformanceMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
