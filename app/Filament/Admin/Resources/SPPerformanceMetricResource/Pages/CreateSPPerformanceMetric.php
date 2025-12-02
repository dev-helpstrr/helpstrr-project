<?php

namespace App\Filament\Admin\Resources\SPPerformanceMetricResource\Pages;

use App\Filament\Admin\Resources\SPPerformanceMetricResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSPPerformanceMetric extends CreateRecord
{
    protected static string $resource = SPPerformanceMetricResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
