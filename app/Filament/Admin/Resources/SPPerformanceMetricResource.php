<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SPPerformanceMetricResource\Pages;
use App\Models\SPPerformanceMetric;
use App\Models\AdminActionLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class SPPerformanceMetricResource extends Resource
{
    protected static ?string $model = SPPerformanceMetric::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationLabel = 'SP Performance';
    
    protected static ?string $navigationGroup = 'Performance & Quality';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('service_provider_id')
                    ->label('Service Provider')
                    ->relationship('serviceProvider', 'name')
                    ->searchable()
                    ->required(),
                    
                Forms\Components\TextInput::make('total_tasks')
                    ->label('Total Tasks')
                    ->numeric()
                    ->default(0),
                    
                Forms\Components\TextInput::make('completed_tasks')
                    ->label('Completed Tasks')
                    ->numeric()
                    ->default(0),
                    
                Forms\Components\TextInput::make('cancelled_tasks')
                    ->label('Cancelled Tasks')
                    ->numeric()
                    ->default(0),
                    
                Forms\Components\TextInput::make('average_rating')
                    ->label('Average Rating')
                    ->numeric()
                    ->step(0.1)
                    ->minValue(0)
                    ->maxValue(5)
                    ->default(0),
                    
                Forms\Components\TextInput::make('total_earnings')
                    ->label('Total Earnings')
                    ->numeric()
                    ->prefix('₹')
                    ->default(0),
                    
                Forms\Components\TextInput::make('punctuality_score')
                    ->label('Punctuality Score')
                    ->numeric()
                    ->step(0.1)
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->default(0),
                    
                Forms\Components\TextInput::make('quality_score')
                    ->label('Quality Score')
                    ->numeric()
                    ->step(0.1)
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->default(0),
                    
                Forms\Components\TextInput::make('response_time_avg')
                    ->label('Avg Response Time (minutes)')
                    ->numeric()
                    ->default(0),
                    
                Forms\Components\TextInput::make('complaints_count')
                    ->label('Complaints Count')
                    ->numeric()
                    ->default(0),
                    
                Forms\Components\KeyValue::make('badges')
                    ->label('Badges & Achievements'),
                    
                Forms\Components\KeyValue::make('incentives')
                    ->label('Incentives & Bonuses'),
                    
                Forms\Components\DatePicker::make('last_updated')
                    ->label('Last Updated')
                    ->default(now())
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
                    
                Tables\Columns\TextColumn::make('total_tasks')
                    ->label('Total Tasks')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('completed_tasks')
                    ->label('Completed')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('completion_rate')
                    ->label('Completion Rate')
                    ->getStateUsing(fn ($record) => $record->total_tasks > 0 ? 
                        round(($record->completed_tasks / $record->total_tasks) * 100, 1) . '%' : '0%')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('average_rating')
                    ->label('Rating')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '/5')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 4.5 => 'success',
                        $state >= 4.0 => 'info',
                        $state >= 3.5 => 'warning',
                        default => 'danger'
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('punctuality_score')
                    ->label('Punctuality')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 90 => 'success',
                        $state >= 80 => 'info',
                        $state >= 70 => 'warning',
                        default => 'danger'
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('quality_score')
                    ->label('Quality')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 90 => 'success',
                        $state >= 80 => 'info',
                        $state >= 70 => 'warning',
                        default => 'danger'
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('total_earnings')
                    ->label('Earnings')
                    ->money('INR')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('complaints_count')
                    ->label('Complaints')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state == 0 => 'success',
                        $state <= 2 => 'warning',
                        default => 'danger'
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('response_time_avg')
                    ->label('Avg Response')
                    ->formatStateUsing(fn ($state) => $state . ' min')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('last_updated')
                    ->label('Last Updated')
                    ->date()
                    ->sortable()
            ])
            ->filters([
                Tables\Filters\Filter::make('high_performers')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('average_rating', '>=', 4.5)
                              ->where('punctuality_score', '>=', 90)
                              ->where('quality_score', '>=', 90))
                    ->label('High Performers'),
                    
                Tables\Filters\Filter::make('needs_improvement')
                    ->query(fn (Builder $query): Builder => 
                        $query->where(function($q) {
                            $q->where('average_rating', '<', 3.5)
                              ->orWhere('punctuality_score', '<', 70)
                              ->orWhere('quality_score', '<', 70)
                              ->orWhere('complaints_count', '>', 3);
                        }))
                    ->label('Needs Improvement'),
                    
                Tables\Filters\Filter::make('active_this_month')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('last_updated', '>=', now()->startOfMonth()))
                    ->label('Active This Month')
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Tables\Actions\Action::make('award_badge')
                    ->label('Award Badge')
                    ->icon('heroicon-o-trophy')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('badge_type')
                            ->label('Badge Type')
                            ->options([
                                'top_performer' => 'Top Performer',
                                'punctual_pro' => 'Punctual Pro',
                                'quality_champion' => 'Quality Champion',
                                'customer_favorite' => 'Customer Favorite',
                                'milestone_achiever' => 'Milestone Achiever'
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('badge_reason')
                            ->label('Reason')
                            ->required()
                    ])
                    ->action(function (SPPerformanceMetric $record, array $data) {
                        $badges = $record->badges ?? [];
                        $badges[] = [
                            'type' => $data['badge_type'],
                            'reason' => $data['badge_reason'],
                            'awarded_at' => now()->toISOString(),
                            'awarded_by' => auth()->id()
                        ];
                        
                        $record->update(['badges' => $badges]);
                        
                        AdminActionLog::logAction(
                            auth()->id(),
                            'badge_award',
                            'SPPerformanceMetric',
                            $record->id,
                            "Awarded {$data['badge_type']} badge to {$record->serviceProvider->name}: {$data['badge_reason']}"
                        );
                        
                        Notification::make()
                            ->title('Badge Awarded Successfully')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('add_incentive')
                    ->label('Add Incentive')
                    ->icon('heroicon-o-gift')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('incentive_type')
                            ->label('Incentive Type')
                            ->options([
                                'performance_bonus' => 'Performance Bonus',
                                'punctuality_bonus' => 'Punctuality Bonus',
                                'quality_bonus' => 'Quality Bonus',
                                'milestone_bonus' => 'Milestone Bonus',
                                'referral_bonus' => 'Referral Bonus'
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('incentive_amount')
                            ->label('Amount')
                            ->numeric()
                            ->prefix('₹')
                            ->required(),
                        Forms\Components\Textarea::make('incentive_reason')
                            ->label('Reason')
                            ->required()
                    ])
                    ->action(function (SPPerformanceMetric $record, array $data) {
                        $incentives = $record->incentives ?? [];
                        $incentives[] = [
                            'type' => $data['incentive_type'],
                            'amount' => $data['incentive_amount'],
                            'reason' => $data['incentive_reason'],
                            'awarded_at' => now()->toISOString(),
                            'awarded_by' => auth()->id()
                        ];
                        
                        $record->update(['incentives' => $incentives]);
                        
                        AdminActionLog::logAction(
                            auth()->id(),
                            'incentive_award',
                            'SPPerformanceMetric',
                            $record->id,
                            "Awarded ₹{$data['incentive_amount']} {$data['incentive_type']} to {$record->serviceProvider->name}: {$data['incentive_reason']}"
                        );
                        
                        Notification::make()
                            ->title('Incentive Added Successfully')
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('performance_review')
                    ->label('Review')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->form([
                        Forms\Components\Textarea::make('review_notes')
                            ->label('Performance Review Notes')
                            ->required(),
                        Forms\Components\Select::make('action_required')
                            ->label('Action Required')
                            ->options([
                                'none' => 'No Action',
                                'training' => 'Additional Training',
                                'warning' => 'Performance Warning',
                                'suspension' => 'Temporary Suspension'
                            ])
                            ->required()
                    ])
                    ->action(function (SPPerformanceMetric $record, array $data) {
                        AdminActionLog::logAction(
                            auth()->id(),
                            'performance_review',
                            'SPPerformanceMetric',
                            $record->id,
                            "Performance review for {$record->serviceProvider->name}: {$data['review_notes']} | Action: {$data['action_required']}"
                        );
                        
                        Notification::make()
                            ->title('Performance Review Recorded')
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
            ->defaultSort('average_rating', 'desc');
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
            'index' => Pages\ListSPPerformanceMetrics::route('/'),
            'create' => Pages\CreateSPPerformanceMetric::route('/create'),
            'edit' => Pages\EditSPPerformanceMetric::route('/{record}/edit'),
            'view' => Pages\ViewSPPerformanceMetric::route('/{record}'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        $needsAttention = static::getModel()::where(function($query) {
            $query->where('average_rating', '<', 3.5)
                  ->orWhere('punctuality_score', '<', 70)
                  ->orWhere('quality_score', '<', 70)
                  ->orWhere('complaints_count', '>', 3);
        })->count();
        
        return $needsAttention > 0 ? (string) $needsAttention : null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
