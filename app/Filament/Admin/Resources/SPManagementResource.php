<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\ServiceProvider;
use App\Models\SPUser;
use App\Models\NewCategory;
use App\Models\NewSubcategory;
use App\Models\ChefCuisine;
use App\Models\DietaryPreference;
use App\Models\ChefAddonFlag;
use App\Models\OptionalFlag;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use App\Filament\Admin\Resources\SPManagementResource\Pages;

class SPManagementResource extends Resource
{
    protected static ?string $model = ServiceProvider::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Manage Providers';
    protected static ?string $pluralModelLabel = 'Service Providers';
    protected static ?string $modelLabel = 'Service Provider';
    protected static ?string $navigationGroup = 'Provider Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Service Provider Management')
                ->tabs([
                    // Basic Information Tab
                    Tabs\Tab::make('Basic Information')
                        ->schema([
                            Section::make('SP User Details')
                                ->relationship('spUser')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('first_name')
                                            ->label('First Name')
                                            ->required()
                                            ->maxLength(100),
                                        TextInput::make('last_name')
                                            ->label('Last Name')
                                            ->required()
                                            ->maxLength(100),
                                    ]),
                                    Grid::make(2)->schema([
                                        TextInput::make('email')
                                            ->label('Email')
                                            ->email()
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('mobile1_number')
                                            ->label('Mobile Number')
                                            ->tel()
                                            ->required()
                                            ->maxLength(15),
                                    ]),
                                    Grid::make(3)->schema([
                                        Select::make('gender')
                                            ->label('Gender')
                                            ->options([
                                                'male' => 'Male',
                                                'female' => 'Female',
                                                'other' => 'Other',
                                            ]),
                                        TextInput::make('age')
                                            ->label('Age')
                                            ->numeric()
                                            ->minValue(18)
                                            ->maxValue(70),
                                        TextInput::make('experience_years')
                                            ->label('Experience (Years)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(50),
                                    ]),
                                    Grid::make(2)->schema([
                                        Toggle::make('is_active')
                                            ->label('Active Status')
                                            ->default(true),
                                        Toggle::make('is_online')
                                            ->label('Online Status')
                                            ->default(false),
                                    ]),
                                ]),
                        ]),

                    // Location & Coverage Tab
                    Tabs\Tab::make('Location & Coverage')
                        ->schema([
                            Section::make('Location Information')
                                ->relationship('spUser')
                                ->schema([
                                    Textarea::make('address')
                                        ->label('Address')
                                        ->rows(3)
                                        ->maxLength(500),
                                    Grid::make(3)->schema([
                                        TextInput::make('city')
                                            ->label('City')
                                            ->maxLength(100),
                                        TextInput::make('state')
                                            ->label('State')
                                            ->maxLength(100),
                                        TextInput::make('pincode')
                                            ->label('Pincode')
                                            ->maxLength(10),
                                    ]),
                                    Grid::make(3)->schema([
                                        TextInput::make('latitude')
                                            ->label('Latitude')
                                            ->numeric()
                                            ->step(0.000001)
                                            ->minValue(-90)
                                            ->maxValue(90),
                                        TextInput::make('longitude')
                                            ->label('Longitude')
                                            ->numeric()
                                            ->step(0.000001)
                                            ->minValue(-180)
                                            ->maxValue(180),
                                        TextInput::make('coverage_radius')
                                            ->label('Coverage Radius (KM)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(100)
                                            ->default(10),
                                    ]),
                                    Grid::make(2)->schema([
                                        TextInput::make('max_travel_distance')
                                            ->label('Max Travel Distance (KM)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(100),
                                        Toggle::make('has_two_wheeler')
                                            ->label('Has Two Wheeler')
                                            ->default(false),
                                    ]),
                                ]),
                        ]),

                    // Performance & Ratings Tab
                    Tabs\Tab::make('Performance & Ratings')
                        ->schema([
                            Section::make('Performance Metrics')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextInput::make('rating')
                                            ->label('Rating')
                                            ->numeric()
                                            ->step(0.1)
                                            ->minValue(0)
                                            ->maxValue(5)
                                            ->default(0),
                                        TextInput::make('total_ratings')
                                            ->label('Total Ratings')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0),
                                        TextInput::make('acceptance_rate')
                                            ->label('Acceptance Rate (%)')
                                            ->numeric()
                                            ->step(0.1)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->default(100),
                                    ]),
                                    Grid::make(3)->schema([
                                        TextInput::make('punctuality_score')
                                            ->label('Punctuality Score (%)')
                                            ->numeric()
                                            ->step(0.1)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->default(100),
                                        TextInput::make('behaviour_score')
                                            ->label('Behaviour Score (%)')
                                            ->numeric()
                                            ->step(0.1)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->default(100),
                                        TextInput::make('cancellation_score')
                                            ->label('Cancellation Score (%)')
                                            ->numeric()
                                            ->step(0.1)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->default(0),
                                    ]),
                                    Grid::make(3)->schema([
                                        TextInput::make('tasks_completed')
                                            ->label('Tasks Completed')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0),
                                        TextInput::make('tasks_cancelled')
                                            ->label('Tasks Cancelled')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0),
                                        TextInput::make('tasks_rejected')
                                            ->label('Tasks Rejected')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0),
                                    ]),
                                ]),
                        ]),

                    // Verification & Status Tab
                    Tabs\Tab::make('Verification & Status')
                        ->schema([
                            Section::make('Verification Status')
                                ->schema([
                                    Grid::make(2)->schema([
                                        Toggle::make('kyc_verified')
                                            ->label('KYC Verified')
                                            ->default(false),
                                        Select::make('kyc_status')
                                            ->label('KYC Status')
                                            ->options([
                                                'pending' => 'Pending',
                                                'approved' => 'Approved',
                                                'rejected' => 'Rejected',
                                                'under_review' => 'Under Review',
                                            ])
                                            ->default('pending'),
                                    ]),
                                    Grid::make(2)->schema([
                                        Toggle::make('is_gold_level')
                                            ->label('Gold Level SP')
                                            ->default(false),
                                        Toggle::make('is_active')
                                            ->label('Active Status')
                                            ->default(true),
                                    ]),
                                    Grid::make(2)->schema([
                                        Toggle::make('is_blocked')
                                            ->label('Blocked Status')
                                            ->default(false),
                                        TextInput::make('block_reason')
                                            ->label('Block Reason')
                                            ->maxLength(255),
                                    ]),
                                ]),
                        ]),

                    // Work Preferences Tab
                    Tabs\Tab::make('Work Preferences')
                        ->schema([
                            Section::make('Work Availability')
                                ->relationship('spUser')
                                ->schema([
                                    Grid::make(2)->schema([
                                        Toggle::make('can_work_weekends')
                                            ->label('Can Work Weekends')
                                            ->default(true),
                                        Toggle::make('can_work_nights')
                                            ->label('Can Work Nights')
                                            ->default(false),
                                    ]),
                                    Grid::make(2)->schema([
                                        TextInput::make('max_daily_working_hours')
                                            ->label('Max Daily Working Hours')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(24)
                                            ->default(8),
                                        TextInput::make('expected_hourly_rate')
                                            ->label('Expected Hourly Rate (₹)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->step(0.01),
                                    ]),
                                    Textarea::make('special_conditions')
                                        ->label('Special Conditions')
                                        ->rows(3)
                                        ->maxLength(500),
                                    Textarea::make('additional_notes')
                                        ->label('Additional Notes')
                                        ->rows(3)
                                        ->maxLength(500),
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
                    ->label('SP ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('spUser.first_name')
                    ->label('Name')
                    ->formatStateUsing(fn ($record) => $record->spUser->first_name . ' ' . $record->spUser->last_name)
                    ->sortable()
                    ->searchable(['spUser.first_name', 'spUser.last_name']),
                TextColumn::make('spUser.email')
                    ->label('Email')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('spUser.mobile1_number')
                    ->label('Mobile')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('spUser.city')
                    ->label('City')
                    ->sortable()
                    ->searchable(),
                BadgeColumn::make('kyc_status')
                    ->label('KYC Status')
                    ->colors([
                        'danger' => 'rejected',
                        'warning' => 'pending',
                        'primary' => 'under_review',
                        'success' => 'approved',
                    ]),
                BooleanColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),
                BooleanColumn::make('spUser.is_online')
                    ->label('Online')
                    ->sortable(),
                BooleanColumn::make('is_gold_level')
                    ->label('Gold Level')
                    ->sortable(),
                TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '/5.0')
                    ->sortable(),
                TextColumn::make('total_ratings')
                    ->label('Total Ratings')
                    ->sortable(),
                TextColumn::make('tasks_completed')
                    ->label('Completed Tasks')
                    ->sortable(),
                TextColumn::make('spUser.last_seen_at')
                    ->label('Last Seen')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('kyc_status')
                    ->label('KYC Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'under_review' => 'Under Review',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
                TernaryFilter::make('spUser.is_online')
                    ->label('Online Status'),
                TernaryFilter::make('is_gold_level')
                    ->label('Gold Level'),
                TernaryFilter::make('kyc_verified')
                    ->label('KYC Verified'),
                TernaryFilter::make('is_blocked')
                    ->label('Blocked Status'),
                SelectFilter::make('spUser.city')
                    ->label('City')
                    ->relationship('spUser', 'city')
                    ->searchable(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Tables\Actions\Action::make('updateLocation')
                    ->label('Update Location')
                    ->icon('heroicon-o-map-pin')
                    ->color('info')
                    ->form([
                        Grid::make(2)->schema([
                            TextInput::make('latitude')
                                ->label('Latitude')
                                ->numeric()
                                ->step(0.000001)
                                ->minValue(-90)
                                ->maxValue(90)
                                ->required(),
                            TextInput::make('longitude')
                                ->label('Longitude')
                                ->numeric()
                                ->step(0.000001)
                                ->minValue(-180)
                                ->maxValue(180)
                                ->required(),
                        ]),
                        TextInput::make('coverage_radius')
                            ->label('Coverage Radius (KM)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100),
                        Textarea::make('address')
                            ->label('Address')
                            ->rows(3),
                    ])
                    ->action(function (ServiceProvider $record, array $data): void {
                        $record->spUser->update([
                            'latitude' => $data['latitude'],
                            'longitude' => $data['longitude'],
                            'coverage_radius' => $data['coverage_radius'] ?? $record->spUser->coverage_radius,
                            'address' => $data['address'] ?? $record->spUser->address,
                            'last_seen_at' => now(),
                        ]);
                    })
                    ->fillForm(fn (ServiceProvider $record): array => [
                        'latitude' => $record->spUser->latitude,
                        'longitude' => $record->spUser->longitude,
                        'coverage_radius' => $record->spUser->coverage_radius,
                        'address' => $record->spUser->address,
                    ]),
                Tables\Actions\Action::make('toggleOnlineStatus')
                    ->label(fn (ServiceProvider $record) => $record->spUser->is_online ? 'Set Offline' : 'Set Online')
                    ->icon('heroicon-o-signal')
                    ->color(fn (ServiceProvider $record) => $record->spUser->is_online ? 'danger' : 'success')
                    ->action(function (ServiceProvider $record): void {
                        $record->spUser->update([
                            'is_online' => !$record->spUser->is_online,
                            'last_seen_at' => now(),
                        ]);
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('setOnline')
                        ->label('Set Online')
                        ->icon('heroicon-o-signal')
                        ->color('success')
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                $record->spUser->update([
                                    'is_online' => true,
                                    'last_seen_at' => now(),
                                ]);
                            }
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('setOffline')
                        ->label('Set Offline')
                        ->icon('heroicon-o-signal-slash')
                        ->color('danger')
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                $record->spUser->update([
                                    'is_online' => false,
                                    'last_seen_at' => now(),
                                ]);
                            }
                        })
                        ->requiresConfirmation(),
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
            'index' => Pages\ListSPManagement::route('/'),
            'create' => Pages\CreateSPManagement::route('/create'),
            'view' => Pages\ViewSPManagement::route('/{record}'),
            'edit' => Pages\EditSPManagement::route('/{record}/edit'),
        ];
    }
}