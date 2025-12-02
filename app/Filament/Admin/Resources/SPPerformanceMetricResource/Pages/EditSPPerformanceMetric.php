<?php

namespace App\Filament\Admin\Resources\SPPerformanceMetricResource\Pages;

use App\Filament\Admin\Resources\SPPerformanceMetricResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSPPerformanceMetric extends EditRecord
{
    protected static string $resource = SPPerformanceMetricResource::class;

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
