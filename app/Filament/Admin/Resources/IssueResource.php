<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\IssueResource\Pages;
use App\Models\Issue;
use App\Models\AdminActionLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class IssueResource extends Resource
{
    protected static ?string $model = Issue::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-circle';
    
    protected static ?string $navigationLabel = 'Issues & Support';
    
    protected static ?string $navigationGroup = 'Customer Support';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('reporter_type')
                    ->label('Reporter Type')
                    ->options([
                        'customer' => 'Customer',
                        'service_provider' => 'Service Provider',
                        'admin' => 'Admin'
                    ])
                    ->required(),
                    
                Forms\Components\TextInput::make('reporter_id')
                    ->label('Reporter ID')
                    ->numeric()
                    ->required(),
                    
                Forms\Components\Select::make('task_id')
                    ->label('Related Task')
                    ->relationship('task', 'task_number')
                    ->searchable(),
                    
                Forms\Components\Select::make('category')
                    ->label('Issue Category')
                    ->options([
                        'service_quality' => 'Service Quality',
                        'payment' => 'Payment Issue',
                        'cancellation' => 'Cancellation',
                        'refund' => 'Refund Request',
                        'technical' => 'Technical Issue',
                        'safety' => 'Safety Concern',
                        'other' => 'Other'
                    ])
                    ->required(),
                    
                Forms\Components\TextInput::make('title')
                    ->label('Issue Title')
                    ->required()
                    ->maxLength(255),
                    
                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->required()
                    ->rows(4),
                    
                Forms\Components\Select::make('priority')
                    ->label('Priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'critical' => 'Critical'
                    ])
                    ->default('medium')
                    ->required(),
                    
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Open',
                        'in_progress' => 'In Progress',
                        'resolved' => 'Resolved',
                        'closed' => 'Closed'
                    ])
                    ->default('open')
                    ->required(),
                    
                Forms\Components\Textarea::make('resolution_notes')
                    ->label('Resolution Notes')
                    ->visible(fn (Forms\Get $get) => in_array($get('status'), ['resolved', 'closed'])),
                    
                Forms\Components\TextInput::make('refund_amount')
                    ->label('Refund Amount')
                    ->numeric()
                    ->prefix('₹')
                    ->visible(fn (Forms\Get $get) => $get('category') === 'refund'),
                    
                Forms\Components\KeyValue::make('metadata')
                    ->label('Additional Information')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Ticket #')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(30),
                    
                Tables\Columns\BadgeColumn::make('category')
                    ->label('Category')
                    ->colors([
                        'danger' => ['safety', 'refund'],
                        'warning' => ['payment', 'cancellation'],
                        'info' => ['technical', 'service_quality'],
                        'secondary' => 'other'
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'service_quality' => 'Service Quality',
                        'payment' => 'Payment',
                        'cancellation' => 'Cancellation',
                        'refund' => 'Refund',
                        'technical' => 'Technical',
                        'safety' => 'Safety',
                        'other' => 'Other',
                        default => $state
                    }),
                    
                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Priority')
                    ->colors([
                        'danger' => 'critical',
                        'warning' => 'high',
                        'info' => 'medium',
                        'secondary' => 'low'
                    ]),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'open',
                        'info' => 'in_progress',
                        'success' => 'resolved',
                        'secondary' => 'closed'
                    ]),
                    
                Tables\Columns\TextColumn::make('reporter_type')
                    ->label('Reporter')
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                    
                Tables\Columns\TextColumn::make('task.task_number')
                    ->label('Task')
                    ->default('N/A'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigned To')
                    ->default('Unassigned'),
                    
                Tables\Columns\TextColumn::make('refund_amount')
                    ->label('Refund')
                    ->money('INR')
                    ->default('N/A')
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'in_progress' => 'In Progress',
                        'resolved' => 'Resolved',
                        'closed' => 'Closed'
                    ]),
                    
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'critical' => 'Critical'
                    ]),
                    
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'service_quality' => 'Service Quality',
                        'payment' => 'Payment Issue',
                        'cancellation' => 'Cancellation',
                        'refund' => 'Refund Request',
                        'technical' => 'Technical Issue',
                        'safety' => 'Safety Concern',
                        'other' => 'Other'
                    ]),
                    
                Tables\Filters\Filter::make('unassigned')
                    ->query(fn (Builder $query): Builder => $query->whereNull('assigned_to'))
                    ->label('Unassigned'),
                    
                Tables\Filters\Filter::make('today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today()))
                    ->label('Created Today')
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Tables\Actions\Action::make('assign')
                    ->label('Assign')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assign To')
                            ->relationship('assignedTo', 'name')
                            ->searchable()
                            ->required()
                    ])
                    ->visible(fn (Issue $record): bool => !$record->assigned_to)
                    ->action(function (Issue $record, array $data) {
                        $record->update([
                            'assigned_to' => $data['assigned_to'],
                            'status' => 'in_progress'
                        ]);
                        
                        AdminActionLog::logAction(
                            auth()->id(),
                            'issue_assignment',
                            'Issue',
                            $record->id,
                            "Assigned issue #{$record->id} to admin user"
                        );
                        
                        Notification::make()
                            ->title('Issue Assigned')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('resolution_notes')
                            ->label('Resolution Notes')
                            ->required(),
                        Forms\Components\TextInput::make('refund_amount')
                            ->label('Refund Amount (if applicable)')
                            ->numeric()
                            ->prefix('₹')
                    ])
                    ->visible(fn (Issue $record): bool => in_array($record->status, ['open', 'in_progress']))
                    ->action(function (Issue $record, array $data) {
                        $record->update([
                            'status' => 'resolved',
                            'resolution_notes' => $data['resolution_notes'],
                            'refund_amount' => $data['refund_amount'] ?? null,
                            'resolved_at' => now(),
                            'resolved_by' => auth()->id()
                        ]);
                        
                        AdminActionLog::logAction(
                            auth()->id(),
                            'issue_resolution',
                            'Issue',
                            $record->id,
                            "Resolved issue #{$record->id}: {$data['resolution_notes']}"
                        );
                        
                        Notification::make()
                            ->title('Issue Resolved')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('escalate')
                    ->label('Escalate')
                    ->icon('heroicon-o-arrow-up')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('priority')
                            ->label('New Priority')
                            ->options([
                                'high' => 'High',
                                'critical' => 'Critical'
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('escalation_reason')
                            ->label('Escalation Reason')
                            ->required()
                    ])
                    ->visible(fn (Issue $record): bool => in_array($record->priority, ['low', 'medium']))
                    ->action(function (Issue $record, array $data) {
                        $record->update([
                            'priority' => $data['priority'],
                            'metadata' => array_merge($record->metadata ?? [], [
                                'escalation_reason' => $data['escalation_reason'],
                                'escalated_at' => now()->toISOString(),
                                'escalated_by' => auth()->id()
                            ])
                        ]);
                        
                        AdminActionLog::logAction(
                            auth()->id(),
                            'issue_escalation',
                            'Issue',
                            $record->id,
                            "Escalated issue #{$record->id} to {$data['priority']} priority: {$data['escalation_reason']}"
                        );
                        
                        Notification::make()
                            ->title('Issue Escalated')
                            ->warning()
                            ->send();
                    }),
                    
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('bulk_assign')
                        ->label('Bulk Assign')
                        ->icon('heroicon-o-user-plus')
                        ->color('primary')
                        ->form([
                            Forms\Components\Select::make('assigned_to')
                                ->label('Assign To')
                                ->relationship('assignedTo', 'name')
                                ->searchable()
                                ->required()
                        ])
                        ->action(function ($records, array $data) {
                            $records->each(function ($record) use ($data) {
                                if (!$record->assigned_to) {
                                    $record->update([
                                        'assigned_to' => $data['assigned_to'],
                                        'status' => 'in_progress'
                                    ]);
                                    
                                    AdminActionLog::logAction(
                                        auth()->id(),
                                        'issue_bulk_assignment',
                                        'Issue',
                                        $record->id,
                                        "Bulk assigned issue #{$record->id}"
                                    );
                                }
                            });
                            
                            Notification::make()
                                ->title('Issues Assigned')
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
            'index' => Pages\ListIssues::route('/'),
            'create' => Pages\CreateIssue::route('/create'),
            'edit' => Pages\EditIssue::route('/{record}/edit'),
            'view' => Pages\ViewIssue::route('/{record}'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['open', 'in_progress'])->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        $criticalCount = static::getModel()::where('priority', 'critical')->whereIn('status', ['open', 'in_progress'])->count();
        return $criticalCount > 0 ? 'danger' : 'warning';
    }
}
