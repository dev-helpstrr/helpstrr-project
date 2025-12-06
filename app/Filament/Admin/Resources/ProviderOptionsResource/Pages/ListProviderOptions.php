<?php

namespace App\Filament\Admin\Resources\ProviderOptionsResource\Pages;

use App\Filament\Admin\Resources\ProviderOptionsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use App\Models\NewCategory;
use App\Models\NewSubcategory;
use App\Models\ChefCuisine;
use App\Models\DietaryPreference;
use App\Models\ChefAddonFlag;
use App\Models\OptionalFlag;

class ListProviderOptions extends ListRecords
{
    protected static string $resource = ProviderOptionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'categories' => Tab::make('Categories')
                ->modifyQueryUsing(fn ($query) => $query->where('1', '0')) // This will be handled by separate resources
                ->badge(NewCategory::count()),
            'subcategories' => Tab::make('Subcategories')
                ->modifyQueryUsing(fn ($query) => $query->where('1', '0'))
                ->badge(NewSubcategory::count()),
            'cuisines' => Tab::make('Cuisines')
                ->modifyQueryUsing(fn ($query) => $query->where('1', '0'))
                ->badge(ChefCuisine::count()),
            'dietary_preferences' => Tab::make('Dietary Preferences')
                ->modifyQueryUsing(fn ($query) => $query->where('1', '0'))
                ->badge(DietaryPreference::count()),
            'addon_flags' => Tab::make('Addon Flags')
                ->modifyQueryUsing(fn ($query) => $query->where('1', '0'))
                ->badge(ChefAddonFlag::count()),
            'optional_flags' => Tab::make('Optional Flags')
                ->modifyQueryUsing(fn ($query) => $query->where('1', '0'))
                ->badge(OptionalFlag::count()),
        ];
    }
}