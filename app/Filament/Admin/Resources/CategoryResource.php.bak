<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Categories';

    protected static ?string $modelLabel = 'Category';

    protected static ?string $pluralModelLabel = 'Categories';

    protected static ?string $navigationGroup = 'Service Management';

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
                            ->maxLength(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $context, $state, callable $set) => 
                                $context === 'create' ? $set('slug', \Str::slug($state)) : null
                            ),
                        
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->helperText('URL-friendly version of the name'),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(500),
                        
                        Forms\Components\FileUpload::make('icon')
                            ->label('Category Icon')
                            ->image()
                            ->maxSize(1024)
                            ->helperText('Upload an icon for this category'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pricing Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('night_multiplier')
                            ->label('Night Multiplier')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(1.00)
                            ->maxValue(3.00)
                            ->default(1.00)
                            ->helperText('Multiplier for night time bookings (10 PM - 6 AM)')
                            ->suffix('x'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Only active categories are visible to customers'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon')
                    ->label('Icon')
                    ->circular()
                    ->size(40),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Slug copied!')
                    ->color('gray'),
                
                Tables\Columns\TextColumn::make('subcategories_count')
                    ->label('Subcategories')
                    ->counts('subcategories')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('night_multiplier')
                    ->label('Night Multiplier')
                    ->formatStateUsing(fn (float $state): string => $state . 'x')
                    ->badge()
                    ->color(fn (float $state): string => $state > 1 ? 'warning' : 'gray'),
                
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                
                Tables\Filters\Filter::make('has_night_surcharge')
                    ->label('Has Night Surcharge')
                    ->query(fn (Builder $query): Builder => $query->where('night_multiplier', '>', 1)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (Category $record): string => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (Category $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Category $record): string => $record->is_active ? 'danger' : 'success')
                    ->action(function (Category $record) {
                        $record->update(['is_active' => !$record->is_active]);
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                        }),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                        }),
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
                        Infolists\Components\ImageEntry::make('icon')
                            ->label('Icon')
                            ->circular()
                            ->size(80),
                        
                        Infolists\Components\TextEntry::make('name')
                            ->label('Name')
                            ->size('lg')
                            ->weight('bold'),
                        
                        Infolists\Components\TextEntry::make('slug')
                            ->label('Slug')
                            ->copyable()
                            ->copyMessage('Slug copied!'),
                        
                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('No description provided')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Pricing Configuration')
                    ->schema([
                        Infolists\Components\TextEntry::make('night_multiplier')
                            ->label('Night Multiplier')
                            ->formatStateUsing(fn (float $state): string => $state . 'x')
                            ->badge()
                            ->color(fn (float $state): string => $state > 1 ? 'warning' : 'gray'),
                        
                        Infolists\Components\TextEntry::make('night_surcharge_percentage')
                            ->label('Night Surcharge')
                            ->formatStateUsing(function (Category $record): string {
                                $percentage = ($record->night_multiplier - 1) * 100;
                                return $percentage > 0 ? '+' . number_format($percentage, 0) . '%' : 'No surcharge';
                            })
                            ->badge()
                            ->color(function (Category $record): string {
                                return $record->night_multiplier > 1 ? 'warning' : 'gray';
                            }),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('subcategories_count')
                            ->label('Total Subcategories')
                            ->formatStateUsing(fn (Category $record): string => $record->subcategories()->count()),
                        
                        Infolists\Components\TextEntry::make('active_subcategories_count')
                            ->label('Active Subcategories')
                            ->formatStateUsing(fn (Category $record): string => $record->subcategories()->active()->count()),
                        
                        Infolists\Components\TextEntry::make('tasks_count')
                            ->label('Total Tasks')
                            ->formatStateUsing(fn (Category $record): string => $record->tasks()->count()),
                        
                        Infolists\Components\TextEntry::make('service_providers_count')
                            ->label('Service Providers')
                            ->formatStateUsing(fn (Category $record): string => $record->spCapabilities()->whereHas('serviceProvider', fn ($q) => $q->active())->distinct('service_provider_id')->count()),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Settings')
                    ->schema([
                        Infolists\Components\TextEntry::make('sort_order')
                            ->label('Sort Order'),
                        
                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                        
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                        
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ])
                    ->columns(2),
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'view' => Pages\ViewCategory::route('/{record}'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}