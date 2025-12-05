<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\NewSubcategoryResource\Pages;
use App\Models\NewSubcategory;
use App\Models\NewCategory;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class NewSubcategoryResource extends Resource
{
    protected static ?string $model = NewSubcategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Sub Categories (Roles)';

    protected static ?string $modelLabel = 'Sub Category';

    protected static ?string $pluralModelLabel = 'Sub Categories';

    protected static ?string $navigationGroup = 'Service Hierarchy';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Sub Category Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Sub Category Name')
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
                            ->unique(NewSubcategory::class, 'slug', ignoreRecord: true)
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

                Forms\Components\Section::make('Pricing & Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('hourly_rate')
                            ->label('Hourly Rate (₹)')
                            ->numeric()
                            ->prefix('₹')
                            ->step(0.01),
                        
                        Forms\Components\TextInput::make('min_hours')
                            ->label('Minimum Hours')
                            ->numeric()
                            ->default(1)
                            ->minValue(1),
                        
                        Forms\Components\TextInput::make('consultation_fee')
                            ->label('Consultation Fee (₹)')
                            ->numeric()
                            ->prefix('₹')
                            ->default(0)
                            ->step(0.01),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Service Settings')
                    ->schema([
                        Forms\Components\Toggle::make('pax_required')
                            ->label('Pax Required')
                            ->helperText('Does this service require multiple people?'),
                        
                        Forms\Components\Toggle::make('recurrence_allowed')
                            ->label('Recurrence Allowed')
                            ->default(true)
                            ->helperText('Can this service be booked repeatedly?'),
                        
                        Forms\Components\Toggle::make('is_event_category')
                            ->label('Event Category')
                            ->helperText('Is this for events/special occasions?'),
                        
                        Forms\Components\Toggle::make('is_takeaway')
                            ->label('Takeaway Service')
                            ->helperText('Is this a takeaway/delivery service?'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status & Ordering')
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

                Forms\Components\Section::make('Category Assignment')
                    ->schema([
                        Forms\Components\Repeater::make('categories')
                            ->label('Assign to Categories')
                            ->relationship('categories')
                            ->schema([
                                Forms\Components\Select::make('id')
                                    ->label('Category')
                                    ->options(NewCategory::active()->pluck('name', 'id'))
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
                                NewCategory::find($state['id'])?->name ?? null
                            )
                            ->addActionLabel('Add Category')
                            ->reorderable(false),
                    ])
                    ->visibleOn('edit'),

                Forms\Components\Section::make('Service Assignment')
                    ->schema([
                        Forms\Components\Repeater::make('services')
                            ->label('Assign Services')
                            ->relationship('services')
                            ->schema([
                                Forms\Components\Select::make('id')
                                    ->label('Service')
                                    ->options(Service::active()->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->distinct(),
                                
                                Forms\Components\Toggle::make('pivot.is_primary')
                                    ->label('Primary Subcategory')
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
                                Service::find($state['id'])?->name ?? null
                            )
                            ->addActionLabel('Add Service')
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
                
                Tables\Columns\TextColumn::make('categories.name')
                    ->label('Categories')
                    ->badge()
                    ->separator(',')
                    ->limit(2),
                
                Tables\Columns\TextColumn::make('hourly_rate')
                    ->label('Hourly Rate')
                    ->money('INR')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('services_count')
                    ->label('Services')
                    ->counts('services')
                    ->badge()
                    ->color('success'),
                
                Tables\Columns\IconColumn::make('pax_required')
                    ->label('Pax')
                    ->boolean(),
                
                Tables\Columns\IconColumn::make('is_event_category')
                    ->label('Event')
                    ->boolean(),
                
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
                
                Tables\Filters\SelectFilter::make('categories')
                    ->label('Category')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload(),
                
                Tables\Filters\TernaryFilter::make('pax_required')
                    ->label('Pax Required'),
                
                Tables\Filters\TernaryFilter::make('is_event_category')
                    ->label('Event Category'),
                
                Tables\Filters\Filter::make('has_services')
                    ->label('Has Services')
                    ->query(fn (Builder $query): Builder => $query->has('services')),
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
                Infolists\Components\Section::make('Sub Category Information')
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

                Infolists\Components\Section::make('Pricing & Configuration')
                    ->schema([
                        Infolists\Components\TextEntry::make('hourly_rate')
                            ->label('Hourly Rate')
                            ->money('INR'),
                        
                        Infolists\Components\TextEntry::make('min_hours')
                            ->label('Minimum Hours'),
                        
                        Infolists\Components\TextEntry::make('consultation_fee')
                            ->label('Consultation Fee')
                            ->money('INR'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Service Settings')
                    ->schema([
                        Infolists\Components\IconEntry::make('pax_required')
                            ->label('Pax Required')
                            ->boolean(),
                        
                        Infolists\Components\IconEntry::make('recurrence_allowed')
                            ->label('Recurrence Allowed')
                            ->boolean(),
                        
                        Infolists\Components\IconEntry::make('is_event_category')
                            ->label('Event Category')
                            ->boolean(),
                        
                        Infolists\Components\IconEntry::make('is_takeaway')
                            ->label('Takeaway Service')
                            ->boolean(),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('categories_count')
                            ->label('Total Categories')
                            ->getStateUsing(fn (NewSubcategory $record): int => $record->categories()->count()),
                        
                        Infolists\Components\TextEntry::make('active_categories_count')
                            ->label('Active Categories')
                            ->getStateUsing(fn (NewSubcategory $record): int => $record->getActiveCategoriesCount()),
                        
                        Infolists\Components\TextEntry::make('services_count')
                            ->label('Total Services')
                            ->getStateUsing(fn (NewSubcategory $record): int => $record->getActiveServicesCount()),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Categories')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('categories')
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

                Infolists\Components\Section::make('Services')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('services')
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
            'index' => Pages\ListNewSubcategories::route('/'),
            'create' => Pages\CreateNewSubcategory::route('/create'),
            'view' => Pages\ViewNewSubcategory::route('/{record}'),
            'edit' => Pages\EditNewSubcategory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['categories', 'services'])
            ->withCount(['categories', 'services']);
    }
}