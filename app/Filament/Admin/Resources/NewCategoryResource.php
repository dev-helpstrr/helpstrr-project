<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\NewCategoryResource\Pages;
use App\Models\NewCategory;
use App\Models\NewSubcategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class NewCategoryResource extends Resource
{
    protected static ?string $model = NewCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Categories';

    protected static ?string $modelLabel = 'Category';

    protected static ?string $pluralModelLabel = 'Categories';

    protected static ?string $navigationGroup = 'Service Hierarchy';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Category Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Category Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $context, $state, callable $set) => 
                                $context === 'create' ? $set('slug', Str::slug($state)) : null
                            ),
                        
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(NewCategory::class, 'slug', ignoreRecord: true)
                            ->rules(['alpha_dash']),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Visual Settings')
                    ->schema([
                        Forms\Components\TextInput::make('icon')
                            ->label('Icon Class')
                            ->placeholder('heroicon-o-star')
                            ->helperText('Use Heroicon class names'),
                        
                        Forms\Components\ColorPicker::make('color')
                            ->label('Color')
                            ->hex(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Subcategories')
                    ->schema([
                        Forms\Components\Repeater::make('subcategories')
                            ->label('Assign Subcategories')
                            ->relationship('subcategories')
                            ->schema([
                                Forms\Components\Select::make('id')
                                    ->label('Subcategory')
                                    ->options(NewSubcategory::active()->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->distinct(),
                                
                                Forms\Components\Toggle::make('pivot.is_primary')
                                    ->label('Primary Category')
                                    ->default(false),
                                
                                Forms\Components\TextInput::make('pivot.sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                NewSubcategory::find($state['id'])?->name ?? null
                            )
                            ->addActionLabel('Add Subcategory')
                            ->reorderable(false),
                    ])
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Slug copied!')
                    ->badge(),
                
                Tables\Columns\ColorColumn::make('color')
                    ->label('Color'),
                
                Tables\Columns\TextColumn::make('subcategories_count')
                    ->label('Subcategories')
                    ->counts('subcategories')
                    ->badge(),
                
                Tables\Columns\TextColumn::make('services_count')
                    ->label('Services')
                    ->getStateUsing(function (NewCategory $record): int {
                        return $record->subcategories()
                            ->withCount('services')
                            ->get()
                            ->sum('services_count');
                    })
                    ->badge()
                    ->color('success'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Sort')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                
                Tables\Filters\Filter::make('has_subcategories')
                    ->label('Has Subcategories')
                    ->query(fn (Builder $query): Builder => $query->has('subcategories')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Category Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Name'),
                        
                        Infolists\Components\TextEntry::make('slug')
                            ->label('Slug')
                            ->badge(),
                        
                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Visual Settings')
                    ->schema([
                        Infolists\Components\TextEntry::make('icon')
                            ->label('Icon'),
                        
                        Infolists\Components\ColorEntry::make('color')
                            ->label('Color'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Settings')
                    ->schema([
                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                        
                        Infolists\Components\TextEntry::make('sort_order')
                            ->label('Sort Order'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('subcategories_count')
                            ->label('Total Subcategories')
                            ->getStateUsing(fn (NewCategory $record): int => $record->subcategories()->count()),
                        
                        Infolists\Components\TextEntry::make('active_subcategories_count')
                            ->label('Active Subcategories')
                            ->getStateUsing(fn (NewCategory $record): int => $record->getActiveSubcategoriesCount()),
                        
                        Infolists\Components\TextEntry::make('services_count')
                            ->label('Total Services')
                            ->getStateUsing(fn (NewCategory $record): int => $record->getServicesCount()),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Subcategories')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('subcategories')
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Name'),
                                
                                Infolists\Components\TextEntry::make('pivot.is_primary')
                                    ->label('Primary')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                                
                                Infolists\Components\TextEntry::make('pivot.sort_order')
                                    ->label('Sort Order'),
                            ])
                            ->columns(3),
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
            'index' => Pages\ListNewCategories::route('/'),
            'create' => Pages\CreateNewCategory::route('/create'),
            'view' => Pages\ViewNewCategory::route('/{record}'),
            'edit' => Pages\EditNewCategory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['subcategories']);
    }
}