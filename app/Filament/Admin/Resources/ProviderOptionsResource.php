<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\NewCategory;
use App\Models\NewSubcategory;
use App\Models\ChefCuisine;
use App\Models\DietaryPreference;
use App\Models\ChefAddonFlag;
use App\Models\OptionalFlag;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\TernaryFilter;
use App\Filament\Admin\Resources\ProviderOptionsResource\Pages;

class ProviderOptionsResource extends Resource
{
    protected static ?string $model = NewCategory::class; // Default model, will be overridden

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Provider Options';
    protected static ?string $pluralModelLabel = 'Provider Options';
    protected static ?string $modelLabel = 'Provider Option';
    protected static ?string $navigationGroup = 'Provider Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Provider Options Management')
                ->tabs([
                    // Categories Tab
                    Tabs\Tab::make('Categories')
                        ->schema([
                            Section::make('Category Management')
                                ->description('Manage service categories available for providers')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Category Name')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('slug')
                                        ->label('Category Slug')
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(ignoreRecord: true),
                                    Textarea::make('description')
                                        ->label('Description')
                                        ->rows(3)
                                        ->maxLength(500),
                                    Toggle::make('is_active')
                                        ->label('Active Status')
                                        ->default(true),
                                    TextInput::make('sort_order')
                                        ->label('Sort Order')
                                        ->numeric()
                                        ->default(0),
                                ]),
                        ]),

                    // Subcategories Tab
                    Tabs\Tab::make('Subcategories')
                        ->schema([
                            Section::make('Subcategory Management')
                                ->description('Manage service subcategories for each category')
                                ->schema([
                                    Select::make('category_id')
                                        ->label('Parent Category')
                                        ->relationship('category', 'name')
                                        ->required()
                                        ->searchable(),
                                    TextInput::make('name')
                                        ->label('Subcategory Name')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('slug')
                                        ->label('Subcategory Slug')
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(ignoreRecord: true),
                                    Textarea::make('description')
                                        ->label('Description')
                                        ->rows(3)
                                        ->maxLength(500),
                                    Toggle::make('is_active')
                                        ->label('Active Status')
                                        ->default(true),
                                    TextInput::make('sort_order')
                                        ->label('Sort Order')
                                        ->numeric()
                                        ->default(0),
                                ]),
                        ]),

                    // Cuisines Tab
                    Tabs\Tab::make('Cuisines')
                        ->schema([
                            Section::make('Cuisine Management')
                                ->description('Manage cuisine types for chef services')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Cuisine Name')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('slug')
                                        ->label('Cuisine Slug')
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(ignoreRecord: true),
                                    Textarea::make('description')
                                        ->label('Description')
                                        ->rows(3)
                                        ->maxLength(500),
                                    Toggle::make('is_active')
                                        ->label('Active Status')
                                        ->default(true),
                                    TextInput::make('sort_order')
                                        ->label('Sort Order')
                                        ->numeric()
                                        ->default(0),
                                ]),
                        ]),

                    // Dietary Preferences Tab
                    Tabs\Tab::make('Dietary Preferences')
                        ->schema([
                            Section::make('Dietary Preference Management')
                                ->description('Manage dietary preferences for food services')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Dietary Preference Name')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('slug')
                                        ->label('Dietary Preference Slug')
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(ignoreRecord: true),
                                    Textarea::make('description')
                                        ->label('Description')
                                        ->rows(3)
                                        ->maxLength(500),
                                    Toggle::make('is_active')
                                        ->label('Active Status')
                                        ->default(true),
                                    TextInput::make('sort_order')
                                        ->label('Sort Order')
                                        ->numeric()
                                        ->default(0),
                                ]),
                        ]),

                    // Addon Flags Tab
                    Tabs\Tab::make('Addon Flags')
                        ->schema([
                            Section::make('Addon Flag Management')
                                ->description('Manage addon flags for special requirements')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Addon Flag Name')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('slug')
                                        ->label('Addon Flag Slug')
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(ignoreRecord: true),
                                    Textarea::make('description')
                                        ->label('Description')
                                        ->rows(3)
                                        ->maxLength(500),
                                    Toggle::make('is_active')
                                        ->label('Active Status')
                                        ->default(true),
                                    TextInput::make('sort_order')
                                        ->label('Sort Order')
                                        ->numeric()
                                        ->default(0),
                                ]),
                        ]),

                    // Optional Flags Tab
                    Tabs\Tab::make('Optional Flags')
                        ->schema([
                            Section::make('Optional Flag Management')
                                ->description('Manage optional flags for provider preferences')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Optional Flag Name')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('slug')
                                        ->label('Optional Flag Slug')
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(ignoreRecord: true),
                                    Textarea::make('description')
                                        ->label('Description')
                                        ->rows(3)
                                        ->maxLength(500),
                                    Select::make('filter_type')
                                        ->label('Filter Type')
                                        ->options([
                                            'hard' => 'Hard Filter (Required)',
                                            'soft' => 'Soft Filter (Preferred)',
                                        ])
                                        ->default('soft'),
                                    Toggle::make('is_active')
                                        ->label('Active Status')
                                        ->default(true),
                                    TextInput::make('sort_order')
                                        ->label('Sort Order')
                                        ->numeric()
                                        ->default(0),
                                ]),
                        ]),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                BooleanColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Sort Order')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                $record->update(['is_active' => true]);
                            }
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                $record->update(['is_active' => false]);
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProviderOptions::route('/'),
            'create' => Pages\CreateProviderOptions::route('/create'),
            'view' => Pages\ViewProviderOptions::route('/{record}'),
            'edit' => Pages\EditProviderOptions::route('/{record}/edit'),
        ];
    }
}