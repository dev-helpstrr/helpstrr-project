<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SimpleOrderResource\Pages;
use App\Models\SimpleOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SimpleOrderResource extends Resource
{
    protected static ?string $model = SimpleOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Customer Orders';

    protected static ?string $modelLabel = 'Customer Order';

    protected static ?string $pluralModelLabel = 'Customer Orders';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('customer_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('service_category_booked')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('customer_ordered_date_time')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service_category_booked')
                    ->label('Service Category Booked')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_ordered_date_time')
                    ->label('Order Date & Time')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListSimpleOrders::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Disable create since orders come from API
    }

    public static function canEdit($record): bool
    {
        return false; // Disable edit since this is read-only
    }
}
