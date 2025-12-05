<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ServiceCategoryResource\Pages;
use App\Models\ServiceCategory;
use App\Models\SortkarJobRole;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class ServiceCategoryResource extends Resource
{
    protected static ?string $model = ServiceCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';
    protected static ?string $navigationGroup = 'Settings';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('role_id')
                ->label('Role')
                ->options(SortkarJobRole::pluck('role_name', 'id')->toArray())
                ->searchable()
                ->required(),

            Select::make('parent_id')
                ->label('Parent Category')
                ->options(ServiceCategory::pluck('name', 'id')->toArray())
                ->searchable()
                ->nullable(),

            TextInput::make('name')->required()->maxLength(191),
            TextInput::make('slug')->nullable()->maxLength(191),
            TextInput::make('sort_order')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('name')->label('Name')->searchable()->sortable(),
                TextColumn::make('role.role_name')->label('Role'),
                TextColumn::make('parent.name')->label('Parent Category'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceCategories::route('/'),
            'create' => Pages\CreateServiceCategory::route('/create'),
            'edit' => Pages\EditServiceCategory::route('/{record}/edit'),
        ];
    }
}
