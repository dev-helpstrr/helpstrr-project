<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EmergencyAlertResource\Pages;
use App\Models\EmergencyAlert;
use App\Models\AdminActionLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class EmergencyAlertResource extends Resource
{
    protected static ?string $model = EmergencyAlert::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    
    protected static ?string $navigationLabel = 'Emergency Alerts';
    
    protected static ?string $navigationGroup = 'Safety & Security';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_type')
                    ->options([
                        'customer' => 'Customer',
                        'service_provider' => 'Service Provider'
                    ])
                    ->required(),
                    
                Forms\Components\TextInput::make('user_id')
                    ->label('User ID')
                    ->numeric()
                    ->required(),
                    
                Forms\Components\Select::make('task_id')
                    ->relationship('task', 'id')
                    ->searchable(),
                    
                Forms\Components\Select::make('alert_type')
                    ->options([
                        'panic_button' => 'Panic Button',
                        'sos' => 'SOS',
                        'safety_concern' => 'Safety Concern',
                        'emergency' => 'Emergency'
                    ])
                    ->required(),
                    
                Forms\Components\Textarea::make('description')
                    ->label('Description'),
                    
                Forms\Components\TextInput::make('latitude')
                    ->numeric()
                    ->step(0.00000001),
                    
                Forms\Components\TextInput::make('longitude')
                    ->numeric()
                    ->step(0.00000001),
                    
                Forms\Components\TextInput::make('location_address')
                    ->label('Location Address'),
                    
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'resolved' => 'Resolved',
                        'false_alarm' => 'False Alarm'
                    ])
                    ->default('active')
                    ->required(),
                    
                Forms\Components\Textarea::make('resolution_notes')
                    ->label('Resolution Notes')
                    ->visible(fn (Forms\Get $get) => in_array($get('status'), ['resolved', 'false_alarm'])),
                    
                Forms\Components\KeyValue::make('metadata')
                    ->label('Additional Information')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('alert_type')
                    ->label('Alert Type')
                    ->colors([
                        'danger' => ['panic_button', 'sos', 'emergency'],
                        'warning' => 'safety_concern'
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'panic_button' => 'Panic Button',
                        'sos' => 'SOS',
                        'safety_concern' => 'Safety Concern',
                        'emergency' => 'Emergency',
                        default => $state
                    }),
                    
                Tables\Columns\TextColumn::make('user_type')
                    ->label('User Type')
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                    
                Tables\Columns\TextColumn::make('user_id')
                    ->label('User ID'),
                    
                Tables\Columns\TextColumn::make('task.id')
                    ->label('Task ID')
                    ->default('N/A'),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'danger' => 'active',
                        'success' => 'resolved',
                        'secondary' => 'false_alarm'
                    ]),
                    
                Tables\Columns\TextColumn::make('location_address')
                    ->label('Location')
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Alert Time')
                    ->dateTime()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('resolvedBy.name')
                    ->label('Resolved By')
                    ->default('Not resolved')
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'resolved' => 'Resolved',
                        'false_alarm' => 'False Alarm'
                    ]),
                    
                Tables\Filters\SelectFilter::make('alert_type')
                    ->options([
                        'panic_button' => 'Panic Button',
                        'sos' => 'SOS',
                        'safety_concern' => 'Safety Concern',
                        'emergency' => 'Emergency'
                    ]),
                    
                Tables\Filters\SelectFilter::make('user_type')
                    ->options([
                        'customer' => 'Customer',
                        'service_provider' => 'Service Provider'
                    ])
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Tables\Actions\Action::make('resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('resolution_notes')
                            ->label('Resolution Notes')
                            ->required()
                    ])
                    ->visible(fn (EmergencyAlert $record): bool => $record->status === 'active')
                    ->action(function (EmergencyAlert $record, array $data) {
                        $record->resolve(auth()->id(), $data['resolution_notes']);
                        
                        AdminActionLog::logAction(
                            auth()->id(),
                            'emergency_resolved',
                            'EmergencyAlert',
                            $record->id,
                            "Resolved emergency alert: {$data['resolution_notes']}"
                        );
                        
                        Notification::make()
                            ->title('Emergency Alert Resolved')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('false_alarm')
                    ->icon('heroicon-o-x-circle')
                    ->color('secondary')
                    ->form([
                        Forms\Components\Textarea::make('resolution_notes')
                            ->label('Notes')
                            ->required()
                    ])
                    ->visible(fn (EmergencyAlert $record): bool => $record->status === 'active')
                    ->action(function (EmergencyAlert $record, array $data) {
                        $record->markFalseAlarm(auth()->id(), $data['resolution_notes']);
                        
                        AdminActionLog::logAction(
                            auth()->id(),
                            'emergency_false_alarm',
                            'EmergencyAlert',
                            $record->id,
                            "Marked as false alarm: {$data['resolution_notes']}"
                        );
                        
                        Notification::make()
                            ->title('Marked as False Alarm')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
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
            'index' => Pages\ListEmergencyAlerts::route('/'),
            'create' => Pages\CreateEmergencyAlert::route('/create'),
            'edit' => Pages\EditEmergencyAlert::route('/{record}/edit'),
            'view' => Pages\ViewEmergencyAlert::route('/{record}'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'active')->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        $activeCount = static::getModel()::where('status', 'active')->count();
        return $activeCount > 0 ? 'danger' : 'success';
    }
}
