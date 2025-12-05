<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ServiceResource\Pages;
use App\Models\Service;
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

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Services';

    protected static ?string $modelLabel = 'Service';

    protected static ?string $pluralModelLabel = 'Services';

    protected static ?string $navigationGroup = 'Service Hierarchy';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Service Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Service Name')
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
                            ->unique(Service::class, 'slug', ignoreRecord: true)
                            ->rules(['alpha_dash']),
                        
                        Forms\Components\Textarea::make('short_description')
                            ->label('Short Description')
                            ->rows(2)
                            ->maxLength(500),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Full Description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Visual & Media')
                    ->schema([
                        Forms\Components\TextInput::make('icon')
                            ->label('Icon Class')
                            ->placeholder('heroicon-o-star')
                            ->helperText('Use Heroicon class names'),
                        
                        Forms\Components\FileUpload::make('image')
                            ->label('Service Image')
                            ->image()
                            ->directory('services')
                            ->visibility('public'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pricing Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('base_price')
                            ->label('Base Price (₹)')
                            ->numeric()
                            ->prefix('₹')
                            ->step(0.01)
                            ->helperText('Fixed price for the service'),
                        
                        Forms\Components\TextInput::make('hourly_rate')
                            ->label('Hourly Rate (₹)')
                            ->numeric()
                            ->prefix('₹')
                            ->step(0.01)
                            ->helperText('Price per hour if hourly billing'),
                        
                        Forms\Components\TextInput::make('consultation_fee')
                            ->label('Consultation Fee (₹)')
                            ->numeric()
                            ->prefix('₹')
                            ->default(0)
                            ->step(0.01),
                        
                        Forms\Components\TextInput::make('travel_allowance')
                            ->label('Travel Allowance (₹)')
                            ->numeric()
                            ->prefix('₹')
                            ->default(0)
                            ->step(0.01),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Time & Capacity Settings')
                    ->schema([
                        Forms\Components\TextInput::make('min_hours')
                            ->label('Minimum Hours')
                            ->numeric()
                            ->default(1)
                            ->minValue(1),
                        
                        Forms\Components\TextInput::make('max_hours')
                            ->label('Maximum Hours')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Leave empty for no limit'),
                        
                        Forms\Components\TextInput::make('min_pax')
                            ->label('Minimum People')
                            ->numeric()
                            ->default(1)
                            ->minValue(1),
                        
                        Forms\Components\TextInput::make('max_pax')
                            ->label('Maximum People')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Leave empty for no limit'),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Service Features')
                    ->schema([
                        Forms\Components\Toggle::make('pax_required')
                            ->label('Pax Required')
                            ->helperText('Does this service require multiple people?'),
                        
                        Forms\Components\Toggle::make('recurrence_allowed')
                            ->label('Recurrence Allowed')
                            ->default(true)
                            ->helperText('Can this service be booked repeatedly?'),
                        
                        Forms\Components\Toggle::make('is_event_service')
                            ->label('Event Service')
                            ->helperText('Is this for events/special occasions?'),
                        
                        Forms\Components\Toggle::make('is_takeaway')
                            ->label('Takeaway Service')
                            ->helperText('Is this a takeaway/delivery service?'),
                        
                        Forms\Components\Toggle::make('requires_verification')
                            ->label('Requires Verification')
                            ->helperText('Does this service need admin verification?'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\KeyValue::make('requirements')
                            ->label('Service Requirements')
                            ->keyLabel('Requirement')
                            ->valueLabel('Description')
                            ->addActionLabel('Add Requirement'),
                        
                        Forms\Components\KeyValue::make('features')
                            ->label('Service Features')
                            ->keyLabel('Feature')
                            ->valueLabel('Description')
                            ->addActionLabel('Add Feature'),
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

                Forms\Components\Section::make('Subcategory Assignment')
                    ->schema([
                        Forms\Components\Repeater::make('subcategories')
                            ->label('Assign to Subcategories')
                            ->relationship('subcategories')
                            ->schema([
                                Forms\Components\Select::make('id')
                                    ->label('Subcategory')
                                    ->options(NewSubcategory::active()->pluck('name', 'id'))
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
                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder.png')),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Slug copied!')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('subcategories.name')
                    ->label('Subcategories')
                    ->badge()
                    ->separator(',')
                    ->limit(2),
                
                Tables\Columns\TextColumn::make('base_price')
                    ->label('Base Price')
                    ->money('INR')
                    ->sortable()
                    ->placeholder('N/A'),
                
                Tables\Columns\TextColumn::make('hourly_rate')
                    ->label('Hourly Rate')
                    ->money('INR')
                    ->sortable()
                    ->placeholder('N/A'),
                
                Tables\Columns\IconColumn::make('pax_required')
                    ->label('Pax')
                    ->boolean(),
                
                Tables\Columns\IconColumn::make('is_event_service')
                    ->label('Event')
                    ->boolean(),
                
                Tables\Columns\IconColumn::make('requires_verification')
                    ->label('Verification')
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
                
                Tables\Filters\SelectFilter::make('subcategories')
                    ->label('Subcategory')
                    ->relationship('subcategories', 'name')
                    ->multiple()
                    ->preload(),
                
                Tables\Filters\TernaryFilter::make('pax_required')
                    ->label('Pax Required'),
                
                Tables\Filters\TernaryFilter::make('is_event_service')
                    ->label('Event Service'),
                
                Tables\Filters\TernaryFilter::make('requires_verification')
                    ->label('Requires Verification'),
                
                Tables\Filters\Filter::make('price_range')
                    ->form([
                        Forms\Components\TextInput::make('price_from')
                            ->label('Price From')
                            ->numeric()
                            ->prefix('₹'),
                        Forms\Components\TextInput::make('price_to')
                            ->label('Price To')
                            ->numeric()
                            ->prefix('₹'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['price_from'],
                                fn (Builder $query, $price): Builder => $query->where(function ($q) use ($price) {
                                    $q->where('base_price', '>=', $price)
                                      ->orWhere('hourly_rate', '>=', $price);
                                })
                            )
                            ->when(
                                $data['price_to'],
                                fn (Builder $query, $price): Builder => $query->where(function ($q) use ($price) {
                                    $q->where('base_price', '<=', $price)
                                      ->orWhere('hourly_rate', '<=', $price);
                                })
                            );
                    }),
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
                Infolists\Components\Section::make('Service Information')
                    ->schema([
                        Infolists\Components\ImageEntry::make('image')
                            ->label('Image')
                            ->height(200),
                        
                        Infolists\Components\TextEntry::make('name')
                            ->label('Name'),
                        
                        Infolists\Components\TextEntry::make('slug')
                            ->label('Slug')
                            ->badge(),
                        
                        Infolists\Components\TextEntry::make('short_description')
                            ->label('Short Description'),
                        
                        Infolists\Components\TextEntry::make('description')
                            ->label('Full Description')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Pricing')
                    ->schema([
                        Infolists\Components\TextEntry::make('base_price')
                            ->label('Base Price')
                            ->money('INR'),
                        
                        Infolists\Components\TextEntry::make('hourly_rate')
                            ->label('Hourly Rate')
                            ->money('INR'),
                        
                        Infolists\Components\TextEntry::make('consultation_fee')
                            ->label('Consultation Fee')
                            ->money('INR'),
                        
                        Infolists\Components\TextEntry::make('travel_allowance')
                            ->label('Travel Allowance')
                            ->money('INR'),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Time & Capacity')
                    ->schema([
                        Infolists\Components\TextEntry::make('min_hours')
                            ->label('Min Hours'),
                        
                        Infolists\Components\TextEntry::make('max_hours')
                            ->label('Max Hours')
                            ->placeholder('No limit'),
                        
                        Infolists\Components\TextEntry::make('min_pax')
                            ->label('Min People'),
                        
                        Infolists\Components\TextEntry::make('max_pax')
                            ->label('Max People')
                            ->placeholder('No limit'),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Service Features')
                    ->schema([
                        Infolists\Components\IconEntry::make('pax_required')
                            ->label('Pax Required')
                            ->boolean(),
                        
                        Infolists\Components\IconEntry::make('recurrence_allowed')
                            ->label('Recurrence Allowed')
                            ->boolean(),
                        
                        Infolists\Components\IconEntry::make('is_event_service')
                            ->label('Event Service')
                            ->boolean(),
                        
                        Infolists\Components\IconEntry::make('is_takeaway')
                            ->label('Takeaway Service')
                            ->boolean(),
                        
                        Infolists\Components\IconEntry::make('requires_verification')
                            ->label('Requires Verification')
                            ->boolean(),
                    ])
                    ->columns(5),

                Infolists\Components\Section::make('Requirements')
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('requirements')
                            ->label('Service Requirements'),
                    ])
                    ->visible(fn (Service $record): bool => !empty($record->requirements)),

                Infolists\Components\Section::make('Features')
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('features')
                            ->label('Service Features'),
                    ])
                    ->visible(fn (Service $record): bool => !empty($record->features)),

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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'view' => Pages\ViewService::route('/{record}'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['subcategories'])
            ->withCount(['subcategories']);
    }
}