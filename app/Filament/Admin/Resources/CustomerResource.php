<?php

namespace App\Filament\Admin\Resources;

use Filament\Tables;
use App\Models\Customer;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\BulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Collection;
use Filament\Infolists\Components\BooleanEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use App\Filament\Admin\Resources\CustomerResource\Pages\EditCustomer;
use App\Filament\Admin\Resources\CustomerResource\Pages\ViewCustomer;
use App\Filament\Admin\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Admin\Resources\CustomerResource\Pages\CreateCustomer;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Customer Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Customer Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter customer full name'),
                        TextInput::make('email')
                            ->required()
                            ->email()
                            ->unique(Customer::class, 'email', ignoreRecord: true)
                            ->placeholder('Enter customer email address'),
                        TextInput::make('phone')
                            ->tel()
                            ->unique(Customer::class, 'phone', ignoreRecord: true)
                            ->placeholder('Enter customer phone number'),
                        TextInput::make('password')
                            ->password()
                            ->required(fn(string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->dehydrated(fn($state) => filled($state))
                            ->placeholder('Enter customer password'),
                        TextInput::make('avatar_url')
                            ->url()
                            ->placeholder('Enter avatar URL (optional)'),
                        Toggle::make('is_active')
                            ->default(true)
                            ->label('Active Status'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\ImageColumn::make('avatar_url')
                        ->searchable()
                        ->circular()
                        ->grow(false)
                        ->getStateUsing(fn($record) => $record->avatar_url
                            ? $record->avatar_url
                            : "https://ui-avatars.com/api/?name=" . urlencode($record->name)),
                    Tables\Columns\TextColumn::make('name')
                        ->searchable()
                        ->weight(FontWeight::Bold)
                        ->description(fn(Customer $record): string => $record->email),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('phone')
                            ->searchable()
                            ->icon('heroicon-o-phone')
                            ->placeholder('No phone')
                            ->grow(false),
                        Tables\Columns\TextColumn::make('created_at')
                            ->icon('heroicon-m-calendar')
                            ->dateTime()
                            ->sortable()
                            ->grow(false),
                    ])->alignStart()->visibleFrom('lg')->space(1),
                    Tables\Columns\IconColumn::make('is_active')
                        ->boolean()
                        ->trueIcon('heroicon-o-check-circle')
                        ->falseIcon('heroicon-o-x-circle')
                        ->trueColor('success')
                        ->falseColor('danger')
                        ->grow(false),
                ]),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Active customers')
                    ->falseLabel('Inactive customers')
                    ->native(false),
                SelectFilter::make('has_phone')
                    ->options([
                        '1' => 'With Phone',
                        '0' => 'Without Phone',
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value'] === '1') {
                            return $query->whereNotNull('phone');
                        } elseif ($data['value'] === '0') {
                            return $query->whereNull('phone');
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('toggle_status')
                    ->label('Toggle Status')
                    ->icon(fn(Customer $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn(Customer $record) => $record->is_active ? 'danger' : 'success')
                    ->action(function (Customer $record) {
                        $record->update(['is_active' => !$record->is_active]);
                    })
                    ->requiresConfirmation()
                    ->modalDescription(
                        fn(Customer $record) =>
                        $record->is_active
                            ? 'This will deactivate the customer account.'
                            : 'This will activate the customer account.'
                    ),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each(fn(Customer $record) => $record->update(['is_active' => true]));
                        })
                        ->requiresConfirmation()
                        ->modalDescription('This will activate all selected customers.'),
                    BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (Collection $records) {
                            $records->each(fn(Customer $record) => $record->update(['is_active' => false]));
                        })
                        ->requiresConfirmation()
                        ->modalDescription('This will deactivate all selected customers.'),
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
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'view' => ViewCustomer::route('/{record}'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Customer Information')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Full Name'),
                        TextEntry::make('email')
                            ->label('Email Address')
                            ->icon('heroicon-m-envelope'),
                        TextEntry::make('phone')
                            ->label('Phone Number')
                            ->icon('heroicon-o-phone')
                            ->placeholder('No phone number'),
                        // replacement
                        IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean() // renders check/cross based on boolean value
                            ->trueIcon('heroicon-o-check-circle')   // optional
                            ->falseIcon('heroicon-o-x-circle')     // optional
                            ->trueColor('success')                  // optional
                            ->falseColor('danger'),
                        TextEntry::make('created_at')
                            ->label('Registration Date')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ])
                    ->columns(2),
                InfolistSection::make('Statistics')
                    ->schema([
                        TextEntry::make('registration_days_ago')
                            ->label('Days Since Registration')
                            ->getStateUsing(
                                fn(Customer $record): string =>
                                number_format($record->registration_days_ago, 1) . ' days'
                            ),
                        TextEntry::make('has_phone')
                            ->label('Phone Provided')
                            ->getStateUsing(fn(Customer $record): string => $record->has_phone ? 'Yes' : 'No')
                            ->color(fn(Customer $record) => $record->has_phone ? 'success' : 'gray'),
                        TextEntry::make('has_avatar')
                            ->label('Avatar Set')
                            ->getStateUsing(fn(Customer $record): string => $record->has_avatar ? 'Yes' : 'No')
                            ->color(fn(Customer $record) => $record->has_avatar ? 'success' : 'gray'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 10 ? 'warning' : 'primary';
    }
}
