<?php

namespace App\Filament\Admin\Resources\IssueResource\Pages;

use App\Filament\Admin\Resources\IssueResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateIssue extends CreateRecord
{
    protected static string $resource = IssueResource::class;
    protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
