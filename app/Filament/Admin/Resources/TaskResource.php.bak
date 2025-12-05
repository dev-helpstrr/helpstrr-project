<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TaskResource\Pages;
use App\Models\Task;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Customer;
use App\Models\ServiceProvider;
use App\Models\CustomerAddress;
use App\Models\AdminActionLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Tasks';

    protected static ?string $modelLabel = 'Task';

    protected static ?string $pluralModelLabel = 'Tasks';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Task Details')
                    ->schema([
                        Forms\Components\TextInput::make('task_number')
                            ->label('Task Number')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('customer_address_id')
                            ->label('Service Address')
                            ->relationship('customerAddress', 'address_line_1')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('subcategory_id', null)),

                        Forms\Components\Select::make('subcategory_id')
                            ->label('Subcategory')
                            ->options(function (callable $get) {
                                $categoryId = $get('category_id');
                                if (!$categoryId) {
                                    return [];
                                }
                                return Subcategory::where('category_id', $categoryId)
                                    ->active()
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('service_provider_id')
                            ->label('Service Provider')
                            ->relationship('serviceProvider.spUser', 'first_name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Service Details')
                    ->schema([
                        Forms\Components\TextInput::make('pax_count')
                            ->label('Number of People')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50),

                        Forms\Components\TextInput::make('requested_hours')
                            ->label('Requested Hours')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(12)
                            ->required(),

                        Forms\Components\TextInput::make('billable_hours')
                            ->label('Billable Hours')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Scheduled Date & Time')
                            ->required()
                            ->minDate(now()->addHours(2)),

                        Forms\Components\Select::make('recurrence_type')
                            ->label('Recurrence')
                            ->options([
                                'one_time' => 'One Time',
                                'two_days' => 'Two Days',
                                'three_days' => 'Three Days',
                            ])
                            ->default('one_time')
                            ->required(),

                        Forms\Components\Select::make('dietary_preference_id')
                            ->label('Dietary Preference')
                            ->relationship('dietaryPreference', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Amount (Excl. GST)')
                            ->numeric()
                            ->prefix('₹')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('gst_amount')
                            ->label('GST Amount')
                            ->numeric()
                            ->prefix('₹')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('final_amount')
                            ->label('Final Amount (Incl. GST)')
                            ->numeric()
                            ->prefix('₹')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('special_instructions')
                            ->label('Special Instructions')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'requested' => 'Requested',
                                'searching' => 'Searching',
                                'assigned' => 'Assigned',
                                'on_the_way' => 'On the Way',
                                'arrived' => 'Arrived',
                                'otp_start_verified' => 'OTP Start Verified',
                                'started' => 'Started',
                                'paused' => 'Paused',
                                'resumed' => 'Resumed',
                                'completed' => 'Completed',
                                'rated' => 'Rated',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('task_number')
                    ->label('Task #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('subcategory.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('serviceProvider.spUser.first_name')
                    ->label('Service Provider')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Not Assigned'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'requested' => 'info',
                        'searching' => 'warning',
                        'assigned', 'on_the_way', 'arrived', 'otp_start_verified' => 'primary',
                        'started', 'resumed' => 'success',
                        'paused' => 'warning',
                        'completed', 'rated' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'requested' => 'Requested',
                        'searching' => 'Searching',
                        'assigned' => 'Assigned',
                        'on_the_way' => 'On the Way',
                        'arrived' => 'Arrived',
                        'otp_start_verified' => 'OTP Verified',
                        'started' => 'In Progress',
                        'paused' => 'Paused',
                        'resumed' => 'Resumed',
                        'completed' => 'Completed',
                        'rated' => 'Rated',
                        'cancelled' => 'Cancelled',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('final_amount')
                    ->label('Amount')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'requested' => 'Requested',
                        'searching' => 'Searching',
                        'assigned' => 'Assigned',
                        'on_the_way' => 'On the Way',
                        'arrived' => 'Arrived',
                        'otp_start_verified' => 'OTP Verified',
                        'started' => 'In Progress',
                        'paused' => 'Paused',
                        'resumed' => 'Resumed',
                        'completed' => 'Completed',
                        'rated' => 'Rated',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),

                Tables\Filters\Filter::make('scheduled_today')
                    ->label('Scheduled Today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),

                Tables\Filters\Filter::make('created_today')
                    ->label('Created Today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('assign_sp')
                    ->label('Assign SP')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('service_provider_id')
                            ->label('Service Provider')
                            ->relationship('serviceProvider', 'name')
                            ->searchable()
                            ->required()
                    ])
                    ->visible(fn (Task $record): bool => in_array($record->status, ['requested', 'searching']))
                    ->action(function (Task $record, array $data) {
                        $record->update([
                            'service_provider_id' => $data['service_provider_id'],
                            'status' => 'assigned',
                            'assigned_at' => now()
                        ]);
                        
                        AdminActionLog::logAction(
                            auth()->id(),
                            'task_assignment',
                            'Task',
                            $record->id,
                            "Manually assigned task {$record->task_number} to service provider"
                        );
                        
                        Notification::make()
                            ->title('Task Assigned Successfully')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('reassign_sp')
                    ->label('Reassign')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('service_provider_id')
                            ->label('New Service Provider')
                            ->relationship('serviceProvider', 'name')
                            ->searchable()
                            ->required(),
                        Forms\Components\Textarea::make('reassignment_reason')
                            ->label('Reassignment Reason')
                            ->required()
                    ])
                    ->visible(fn (Task $record): bool => $record->service_provider_id && in_array($record->status, ['assigned', 'on_the_way']))
                    ->action(function (Task $record, array $data) {
                        $oldSP = $record->serviceProvider;
                        $record->update([
                            'service_provider_id' => $data['service_provider_id'],
                            'status' => 'assigned',
                            'assigned_at' => now()
                        ]);
                        
                        AdminActionLog::logAction(
                            auth()->id(),
                            'task_reassignment',
                            'Task',
                            $record->id,
                            "Reassigned task {$record->task_number} from {$oldSP->name} to new SP. Reason: {$data['reassignment_reason']}"
                        );
                        
                        Notification::make()
                            ->title('Task Reassigned Successfully')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('emergency_intervention')
                    ->label('Emergency')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Select::make('intervention_type')
                            ->label('Intervention Type')
                            ->options([
                                'cancel_task' => 'Cancel Task',
                                'emergency_reassign' => 'Emergency Reassignment',
                                'customer_support' => 'Customer Support Call',
                                'sp_support' => 'SP Support Call'
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('intervention_notes')
                            ->label('Notes')
                            ->required()
                    ])
                    ->visible(fn (Task $record): bool => in_array($record->status, ['assigned', 'on_the_way', 'arrived', 'started']))
                    ->action(function (Task $record, array $data) {
                        if ($data['intervention_type'] === 'cancel_task') {
                            $record->update(['status' => 'cancelled']);
                        }
                        
                        AdminActionLog::logAction(
                            auth()->id(),
                            'emergency_intervention',
                            'Task',
                            $record->id,
                            "Emergency intervention on task {$record->task_number}: {$data['intervention_type']} - {$data['intervention_notes']}"
                        );
                        
                        Notification::make()
                            ->title('Emergency Intervention Logged')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Task Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('task_number')
                            ->label('Task Number'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'requested' => 'info',
                                'searching' => 'warning',
                                'assigned', 'on_the_way', 'arrived', 'otp_start_verified' => 'primary',
                                'started', 'resumed' => 'success',
                                'paused' => 'warning',
                                'completed', 'rated' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('scheduled_at')
                            ->label('Scheduled At')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Customer & Service Provider')
                    ->schema([
                        Infolists\Components\TextEntry::make('customer.name')
                            ->label('Customer'),
                        Infolists\Components\TextEntry::make('customer.phone')
                            ->label('Customer Phone'),
                        Infolists\Components\TextEntry::make('serviceProvider.spUser.name')
                            ->label('Service Provider')
                            ->placeholder('Not Assigned'),
                        Infolists\Components\TextEntry::make('serviceProvider.spUser.phone')
                            ->label('SP Phone')
                            ->placeholder('Not Assigned'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Service Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('category.name')
                            ->label('Category'),
                        Infolists\Components\TextEntry::make('subcategory.name')
                            ->label('Subcategory'),
                        Infolists\Components\TextEntry::make('pax_count')
                            ->label('Number of People')
                            ->placeholder('Not specified'),
                        Infolists\Components\TextEntry::make('requested_hours')
                            ->label('Requested Hours'),
                        Infolists\Components\TextEntry::make('billable_hours')
                            ->label('Billable Hours'),
                        Infolists\Components\TextEntry::make('recurrence_type')
                            ->label('Recurrence')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'one_time' => 'One Time',
                                'two_days' => 'Two Days',
                                'three_days' => 'Three Days',
                                default => $state,
                            }),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Address')
                    ->schema([
                        Infolists\Components\TextEntry::make('customerAddress.full_address')
                            ->label('Service Address')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Pricing')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Total (Excl. GST)')
                            ->money('INR'),
                        Infolists\Components\TextEntry::make('gst_amount')
                            ->label('GST Amount')
                            ->money('INR'),
                        Infolists\Components\TextEntry::make('final_amount')
                            ->label('Final Amount')
                            ->money('INR'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Additional Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('special_instructions')
                            ->label('Special Instructions')
                            ->placeholder('None')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('start_otp')
                            ->label('Start OTP')
                            ->placeholder('Not generated'),
                        Infolists\Components\TextEntry::make('end_otp')
                            ->label('End OTP')
                            ->placeholder('Not generated'),
                        Infolists\Components\TextEntry::make('customer_rating')
                            ->label('Customer Rating')
                            ->placeholder('Not rated'),
                        Infolists\Components\TextEntry::make('sp_rating')
                            ->label('SP Rating')
                            ->placeholder('Not rated'),
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
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'view' => Pages\ViewTask::route('/{record}'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'requested')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
