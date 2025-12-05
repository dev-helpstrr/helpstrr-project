<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ServiceBookingResource\Pages;
use App\Models\Task;
use App\Models\Category;
use App\Models\ServiceProvider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ServiceBookingResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationLabel = 'Service Bookings';
    
    protected static ?string $modelLabel = 'Service Booking';
    
    protected static ?string $pluralModelLabel = 'Service Bookings';
    
    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('task_number')
                    ->label('Booking #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'info' => Task::STATUS_REQUESTED,
                        'warning' => [Task::STATUS_SEARCHING, Task::STATUS_PAUSED],
                        'primary' => [Task::STATUS_ASSIGNED, Task::STATUS_ON_THE_WAY, Task::STATUS_ARRIVED, Task::STATUS_OTP_START_VERIFIED],
                        'success' => [Task::STATUS_STARTED, Task::STATUS_RESUMED, Task::STATUS_COMPLETED, Task::STATUS_RATED],
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Task::STATUS_REQUESTED => 'New Booking',
                        Task::STATUS_SEARCHING => 'Finding Provider',
                        Task::STATUS_ASSIGNED => 'Provider Assigned',
                        Task::STATUS_ON_THE_WAY => 'Provider En Route',
                        Task::STATUS_ARRIVED => 'Provider Arrived',
                        Task::STATUS_OTP_START_VERIFIED => 'Service Starting',
                        Task::STATUS_STARTED => 'Service In Progress',
                        Task::STATUS_PAUSED => 'Service Paused',
                        Task::STATUS_RESUMED => 'Service Resumed',
                        Task::STATUS_COMPLETED => 'Service Completed',
                        Task::STATUS_RATED => 'Rated & Closed',
                        'cancelled' => 'Cancelled',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Task $record): string => $record->customer->phone ?? ''),
                
                Tables\Columns\TextColumn::make('serviceProvider.spUser.name')
                    ->label('Service Provider')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Not Assigned')
                    ->description(fn (Task $record): ?string => 
                        $record->serviceProvider ? 
                        "⭐ {$record->serviceProvider->rating}/5 • {$record->serviceProvider->spUser->phone}" : 
                        null
                    ),
                
                Tables\Columns\TextColumn::make('service_details')
                    ->label('Service Details')
                    ->formatStateUsing(fn (Task $record): string => 
                        "{$record->category->name} • {$record->subcategory->name}"
                    )
                    ->description(fn (Task $record): string => 
                        "{$record->pax_count} pax • {$record->requested_hours}h • ₹" . number_format($record->final_amount, 2)
                    ),
                
                Tables\Columns\TextColumn::make('customerAddress.location')
                    ->label('Location')
                    ->formatStateUsing(fn (Task $record): string => 
                        "{$record->customerAddress->city}, {$record->customerAddress->pincode}"
                    )
                    ->description(fn (Task $record): string => 
                        $record->customerAddress->address_line_1
                    ),
                
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime('d M Y')
                    ->description(fn (Task $record): string => 
                        $record->scheduled_at->format('h:i A')
                    )
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('final_amount')
                    ->label('Amount')
                    ->money('INR')
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('customer_rating')
                    ->label('Rating')
                    ->formatStateUsing(fn (?int $state): string => 
                        $state ? "⭐ {$state}/5" : 'Not Rated'
                    )
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Booked On')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        Task::STATUS_REQUESTED => 'New Bookings',
                        Task::STATUS_SEARCHING => 'Finding Provider',
                        Task::STATUS_ASSIGNED => 'Provider Assigned',
                        Task::STATUS_ON_THE_WAY => 'Provider En Route',
                        Task::STATUS_ARRIVED => 'Provider Arrived',
                        Task::STATUS_STARTED => 'Service In Progress',
                        Task::STATUS_COMPLETED => 'Service Completed',
                        Task::STATUS_RATED => 'Rated & Closed',
                        'cancelled' => 'Cancelled',
                    ]),
                
                SelectFilter::make('category_id')
                    ->label('Service Category')
                    ->relationship('category', 'name'),
                
                Filter::make('today_bookings')
                    ->label('Today\'s Bookings')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today())),
                
                Filter::make('tomorrow_bookings')
                    ->label('Tomorrow\'s Bookings')
                    ->query(fn (Builder $query): Builder => $query->whereDate('scheduled_at', today()->addDay())),
                
                Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])),
                
                Filter::make('active_bookings')
                    ->label('Active Bookings')
                    ->query(fn (Builder $query): Builder => $query->whereIn('status', [
                        Task::STATUS_ASSIGNED,
                        Task::STATUS_ON_THE_WAY,
                        Task::STATUS_ARRIVED,
                        Task::STATUS_STARTED,
                        Task::STATUS_PAUSED,
                        Task::STATUS_RESUMED,
                    ])),
                
                Filter::make('unassigned_bookings')
                    ->label('Unassigned Bookings')
                    ->query(fn (Builder $query): Builder => $query->whereIn('status', [
                        Task::STATUS_REQUESTED,
                        Task::STATUS_SEARCHING,
                    ])),
                
                Filter::make('high_value')
                    ->label('High Value (₹2000+)')
                    ->query(fn (Builder $query): Builder => $query->where('final_amount', '>=', 2000)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View Details'),
                
                Tables\Actions\Action::make('assign_provider')
                    ->label('Assign Provider')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->visible(fn (Task $record): bool => in_array($record->status, [Task::STATUS_REQUESTED, Task::STATUS_SEARCHING]))
                    ->form([
                        Forms\Components\Select::make('service_provider_id')
                            ->label('Service Provider')
                            ->options(function (Task $record) {
                                return ServiceProvider::with('spUser')
                                    ->active()
                                    ->verified()
                                    ->byCategory($record->category_id)
                                    ->get()
                                    ->mapWithKeys(function ($sp) {
                                        return [$sp->id => "{$sp->spUser->name} (⭐ {$sp->rating}/5)"];
                                    });
                            })
                            ->required()
                            ->searchable(),
                        Forms\Components\Textarea::make('assignment_notes')
                            ->label('Assignment Notes')
                            ->placeholder('Optional notes for the assignment'),
                    ])
                    ->action(function (Task $record, array $data): void {
                        $record->update([
                            'service_provider_id' => $data['service_provider_id'],
                            'status' => Task::STATUS_ASSIGNED,
                            'assigned_at' => now(),
                        ]);
                        
                        // Log the action
                        activity()
                            ->performedOn($record)
                            ->withProperties([
                                'service_provider_id' => $data['service_provider_id'],
                                'notes' => $data['assignment_notes'] ?? null,
                            ])
                            ->log('Manually assigned service provider');
                    }),
                
                Tables\Actions\Action::make('contact_customer')
                    ->label('Contact Customer')
                    ->icon('heroicon-o-phone')
                    ->color('info')
                    ->url(fn (Task $record): string => "tel:{$record->customer->phone}")
                    ->openUrlInNewTab(),
                
                Tables\Actions\Action::make('contact_provider')
                    ->label('Contact Provider')
                    ->icon('heroicon-o-phone')
                    ->color('success')
                    ->visible(fn (Task $record): bool => $record->serviceProvider !== null)
                    ->url(fn (Task $record): string => "tel:{$record->serviceProvider->spUser->phone}")
                    ->openUrlInNewTab(),
                
                Tables\Actions\Action::make('cancel_booking')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Task $record): bool => !in_array($record->status, [Task::STATUS_COMPLETED, Task::STATUS_RATED, 'cancelled']))
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->required(),
                    ])
                    ->action(function (Task $record, array $data): void {
                        $record->update([
                            'status' => 'cancelled',
                            'cancelled_at' => now(),
                            'cancellation_reason' => $data['cancellation_reason'],
                            'cancelled_by' => 'admin',
                        ]);
                        
                        // Log the action
                        activity()
                            ->performedOn($record)
                            ->withProperties(['reason' => $data['cancellation_reason']])
                            ->log('Booking cancelled by admin');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_bookings')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            // Export functionality would go here
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Booking Overview')
                    ->schema([
                        Infolists\Components\TextEntry::make('task_number')
                            ->label('Booking Number')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Current Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                Task::STATUS_REQUESTED => 'info',
                                Task::STATUS_SEARCHING => 'warning',
                                Task::STATUS_ASSIGNED, Task::STATUS_ON_THE_WAY => 'primary',
                                Task::STATUS_STARTED, Task::STATUS_COMPLETED => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Booking Created')
                            ->dateTime('d M Y, h:i A'),
                        Infolists\Components\TextEntry::make('scheduled_at')
                            ->label('Scheduled For')
                            ->dateTime('d M Y, h:i A'),
                    ])->columns(2),

                Infolists\Components\Section::make('Customer Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('customer.name')
                            ->label('Customer Name'),
                        Infolists\Components\TextEntry::make('customer.phone')
                            ->label('Phone Number')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('customer.email')
                            ->label('Email')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('customerAddress.full_address')
                            ->label('Service Address')
                            ->formatStateUsing(fn (Task $record): string => 
                                "{$record->customerAddress->address_line_1}, " .
                                ($record->customerAddress->address_line_2 ? "{$record->customerAddress->address_line_2}, " : "") .
                                "{$record->customerAddress->city}, {$record->customerAddress->state} - {$record->customerAddress->pincode}"
                            ),
                    ])->columns(2),

                Infolists\Components\Section::make('Service Provider Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('serviceProvider.spUser.name')
                            ->label('Provider Name')
                            ->placeholder('Not Assigned'),
                        Infolists\Components\TextEntry::make('serviceProvider.spUser.phone')
                            ->label('Provider Phone')
                            ->copyable()
                            ->placeholder('Not Assigned'),
                        Infolists\Components\TextEntry::make('serviceProvider.rating')
                            ->label('Provider Rating')
                            ->formatStateUsing(fn (?float $state): string => $state ? "⭐ {$state}/5" : 'No Rating'),
                        Infolists\Components\TextEntry::make('assigned_at')
                            ->label('Assigned At')
                            ->dateTime('d M Y, h:i A')
                            ->placeholder('Not Assigned'),
                    ])->columns(2),

                Infolists\Components\Section::make('Service Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('category.name')
                            ->label('Service Category'),
                        Infolists\Components\TextEntry::make('subcategory.name')
                            ->label('Service Subcategory'),
                        Infolists\Components\TextEntry::make('service.name')
                            ->label('Specific Service'),
                        Infolists\Components\TextEntry::make('pax_count')
                            ->label('Number of People'),
                        Infolists\Components\TextEntry::make('requested_hours')
                            ->label('Requested Duration')
                            ->formatStateUsing(fn (int $state): string => "{$state} hours"),
                        Infolists\Components\TextEntry::make('billable_hours')
                            ->label('Actual Duration')
                            ->formatStateUsing(fn (?int $state): string => $state ? "{$state} hours" : 'Not completed'),
                    ])->columns(3),

                Infolists\Components\Section::make('Pricing Breakdown')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Amount (Excl. GST)')
                            ->money('INR'),
                        Infolists\Components\TextEntry::make('gst_amount')
                            ->label('GST Amount')
                            ->money('INR'),
                        Infolists\Components\TextEntry::make('final_amount')
                            ->label('Total Amount')
                            ->money('INR')
                            ->weight('bold'),
                    ])->columns(3),

                Infolists\Components\Section::make('Service Timeline')
                    ->schema([
                        Infolists\Components\TextEntry::make('started_at')
                            ->label('Service Started')
                            ->dateTime('d M Y, h:i A')
                            ->placeholder('Not Started'),
                        Infolists\Components\TextEntry::make('completed_at')
                            ->label('Service Completed')
                            ->dateTime('d M Y, h:i A')
                            ->placeholder('Not Completed'),
                        Infolists\Components\TextEntry::make('cancelled_at')
                            ->label('Cancelled At')
                            ->dateTime('d M Y, h:i A')
                            ->placeholder('Not Cancelled'),
                    ])->columns(3),

                Infolists\Components\Section::make('Feedback & Ratings')
                    ->schema([
                        Infolists\Components\TextEntry::make('customer_rating')
                            ->label('Customer Rating')
                            ->formatStateUsing(fn (?int $state): string => $state ? "⭐ {$state}/5" : 'Not Rated'),
                        Infolists\Components\TextEntry::make('customer_feedback')
                            ->label('Customer Feedback')
                            ->placeholder('No Feedback Provided'),
                        Infolists\Components\TextEntry::make('sp_rating')
                            ->label('Provider Rating for Customer')
                            ->formatStateUsing(fn (?int $state): string => $state ? "⭐ {$state}/5" : 'Not Rated'),
                        Infolists\Components\TextEntry::make('sp_feedback')
                            ->label('Provider Feedback')
                            ->placeholder('No Feedback Provided'),
                    ])->columns(2),

                Infolists\Components\Section::make('Additional Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('special_instructions')
                            ->label('Special Instructions')
                            ->placeholder('No Special Instructions'),
                        Infolists\Components\TextEntry::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->placeholder('Not Cancelled'),
                        Infolists\Components\TextEntry::make('cancelled_by')
                            ->label('Cancelled By')
                            ->placeholder('Not Cancelled'),
                    ])->columns(1),
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
            'index' => Pages\ListServiceBookings::route('/'),
            'view' => Pages\ViewServiceBooking::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', [
            Task::STATUS_REQUESTED,
            Task::STATUS_SEARCHING,
        ])->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false; // Bookings are created via API
    }

    public static function canEdit($record): bool
    {
        return false; // Bookings are managed via actions
    }

    public static function canDelete($record): bool
    {
        return false; // Use cancel action instead
    }
}