<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PayoutResource\Pages;
use App\Models\Payout;
use App\Models\AdminActionLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationLabel = 'Payouts';
    
    protected static ?string $navigationGroup = 'Finance Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('service_provider_id')
                    ->label('Service Provider')
                    ->relationship('serviceProvider', 'name')
                    ->searchable()
                    ->required(),
                    
                Forms\Components\Select::make('task_id')
                    ->label('Task')
                    ->relationship('task', 'task_number')
                    ->searchable(),
                    
                Forms\Components\TextInput::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->prefix('₹')
                    ->required(),
                    
                Forms\Components\TextInput::make('platform_fee')
                    ->label('Platform Fee')
                    ->numeric()
                    ->prefix('₹')
                    ->default(0),
                    
                Forms\Components\TextInput::make('net_amount')
                    ->label('Net Amount')
                    ->numeric()
                    ->prefix('₹')
                    ->required(),
                    
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled'
                    ])
                    ->default('pending')
                    ->required(),
                    
                Forms\Components\Select::make('payment_method')
                    ->options([
                        'bank_transfer' => 'Bank Transfer',
                        'upi' => 'UPI',
                        'wallet' => 'Wallet'
                    ])
                    ->required(),
                    
                Forms\Components\TextInput::make('transaction_id')
                    ->label('Transaction ID'),
                    
                Forms\Components\Textarea::make('notes')
                    ->label('Notes'),
                    
                Forms\Components\DateTimePicker::make('processed_at')
                    ->label('Processed At'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('serviceProvider.name')
                    ->label('Service Provider')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('task.task_number')
                    ->label('Task')
                    ->searchable()
                    ->default('N/A'),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('INR')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('platform_fee')
                    ->label('Platform Fee')
                    ->money('INR')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('net_amount')
                    ->label('Net Amount')
                    ->money('INR')
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'processing',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'secondary' => 'cancelled'
                    ]),
                    
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'bank_transfer' => 'Bank Transfer',
                        'upi' => 'UPI',
                        'wallet' => 'Wallet',
                        default => $state
                    }),
                    
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Processed')
                    ->dateTime()
                    ->sortable()
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled'
                    ]),
                    
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'bank_transfer' => 'Bank Transfer',
                        'upi' => 'UPI',
                        'wallet' => 'Wallet'
                    ]),
                    
                Tables\Filters\Filter::make('pending_approval')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'pending'))
                    ->label('Pending Approval'),
                    
                Tables\Filters\Filter::make('today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today()))
                    ->label('Created Today')
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Payout $record): bool => $record->status === 'pending')
                    ->action(function (Payout $record) {
                        $record->update([
                            'status' => 'processing',
                            'processed_at' => now()
                        ]);
                        
                        AdminActionLog::logAction(
                            auth()->id(),
                            'payout_approval',
                            'Payout',
                            $record->id,
                            "Approved payout of ₹{$record->net_amount} for {$record->serviceProvider->name}"
                        );
                        
                        Notification::make()
                            ->title('Payout Approved')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('complete')
                    ->label('Mark Complete')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('transaction_id')
                            ->label('Transaction ID')
                            ->required()
                    ])
                    ->visible(fn (Payout $record): bool => $record->status === 'processing')
                    ->action(function (Payout $record, array $data) {
                        $record->update([
                            'status' => 'completed',
                            'transaction_id' => $data['transaction_id'],
                            'processed_at' => now()
                        ]);
                        
                        AdminActionLog::logAction(
                            auth()->id(),
                            'payout_completion',
                            'Payout',
                            $record->id,
                            "Completed payout of ₹{$record->net_amount} with transaction ID: {$data['transaction_id']}"
                        );
                        
                        Notification::make()
                            ->title('Payout Completed')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required()
                    ])
                    ->visible(fn (Payout $record): bool => in_array($record->status, ['pending', 'processing']))
                    ->action(function (Payout $record, array $data) {
                        $record->update([
                            'status' => 'failed',
                            'notes' => $data['rejection_reason']
                        ]);
                        
                        AdminActionLog::logAction(
                            auth()->id(),
                            'payout_rejection',
                            'Payout',
                            $record->id,
                            "Rejected payout: {$data['rejection_reason']}"
                        );
                        
                        Notification::make()
                            ->title('Payout Rejected')
                            ->danger()
                            ->send();
                    }),
                    
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('Bulk Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->status === 'pending') {
                                    $record->update([
                                        'status' => 'processing',
                                        'processed_at' => now()
                                    ]);
                                    
                                    AdminActionLog::logAction(
                                        auth()->id(),
                                        'payout_bulk_approval',
                                        'Payout',
                                        $record->id,
                                        "Bulk approved payout of ₹{$record->net_amount}"
                                    );
                                }
                            });
                            
                            Notification::make()
                                ->title('Payouts Approved')
                                ->success()
                                ->send();
                        })
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
            'index' => Pages\ListPayouts::route('/'),
            'create' => Pages\CreatePayout::route('/create'),
            'edit' => Pages\EditPayout::route('/{record}/edit'),
            'view' => Pages\ViewPayout::route('/{record}'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }
}
