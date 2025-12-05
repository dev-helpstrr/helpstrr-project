<?php

namespace App\Filament\Admin\Resources\ServiceBookingResource\Pages;

use App\Filament\Admin\Resources\ServiceBookingResource;
use App\Models\Task;
use App\Models\ServiceProvider;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;

class ViewServiceBooking extends ViewRecord
{
    protected static string $resource = ServiceBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('assign_provider')
                ->label('Assign Provider')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->visible(fn (): bool => in_array($this->record->status, [Task::STATUS_REQUESTED, Task::STATUS_SEARCHING]))
                ->form([
                    Forms\Components\Select::make('service_provider_id')
                        ->label('Service Provider')
                        ->options(function () {
                            return ServiceProvider::with('spUser')
                                ->active()
                                ->verified()
                                ->byCategory($this->record->category_id)
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
                ->action(function (array $data): void {
                    $this->record->update([
                        'service_provider_id' => $data['service_provider_id'],
                        'status' => Task::STATUS_ASSIGNED,
                        'assigned_at' => now(),
                    ]);
                    
                    // Log the action
                    activity()
                        ->performedOn($this->record)
                        ->withProperties([
                            'service_provider_id' => $data['service_provider_id'],
                            'notes' => $data['assignment_notes'] ?? null,
                        ])
                        ->log('Manually assigned service provider');
                        
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Actions\Action::make('contact_customer')
                ->label('Call Customer')
                ->icon('heroicon-o-phone')
                ->color('info')
                ->url(fn (): string => "tel:{$this->record->customer->phone}")
                ->openUrlInNewTab(),

            Actions\Action::make('contact_provider')
                ->label('Call Provider')
                ->icon('heroicon-o-phone')
                ->color('success')
                ->visible(fn (): bool => $this->record->serviceProvider !== null)
                ->url(fn (): string => "tel:{$this->record->serviceProvider->spUser->phone}")
                ->openUrlInNewTab(),

            Actions\Action::make('update_status')
                ->label('Update Status')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => !in_array($this->record->status, [Task::STATUS_COMPLETED, Task::STATUS_RATED, 'cancelled']))
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('New Status')
                        ->options([
                            Task::STATUS_ASSIGNED => 'Assigned',
                            Task::STATUS_ON_THE_WAY => 'On The Way',
                            Task::STATUS_ARRIVED => 'Arrived',
                            Task::STATUS_STARTED => 'Started',
                            Task::STATUS_PAUSED => 'Paused',
                            Task::STATUS_RESUMED => 'Resumed',
                            Task::STATUS_COMPLETED => 'Completed',
                        ])
                        ->required(),
                    Forms\Components\Textarea::make('status_notes')
                        ->label('Status Update Notes')
                        ->placeholder('Optional notes for the status update'),
                ])
                ->action(function (array $data): void {
                    $oldStatus = $this->record->status;
                    
                    $updateData = ['status' => $data['status']];
                    
                    // Add timestamps for specific status updates
                    switch ($data['status']) {
                        case Task::STATUS_ASSIGNED:
                            $updateData['assigned_at'] = now();
                            break;
                        case Task::STATUS_STARTED:
                            $updateData['started_at'] = now();
                            break;
                        case Task::STATUS_COMPLETED:
                            $updateData['completed_at'] = now();
                            break;
                    }
                    
                    $this->record->update($updateData);
                    
                    // Log the action
                    activity()
                        ->performedOn($this->record)
                        ->withProperties([
                            'old_status' => $oldStatus,
                            'new_status' => $data['status'],
                            'notes' => $data['status_notes'] ?? null,
                        ])
                        ->log('Status updated by admin');
                        
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Actions\Action::make('cancel_booking')
                ->label('Cancel Booking')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => !in_array($this->record->status, [Task::STATUS_COMPLETED, Task::STATUS_RATED, 'cancelled']))
                ->requiresConfirmation()
                ->modalHeading('Cancel Booking')
                ->modalDescription('Are you sure you want to cancel this booking? This action cannot be undone.')
                ->form([
                    Forms\Components\Textarea::make('cancellation_reason')
                        ->label('Cancellation Reason')
                        ->required()
                        ->placeholder('Please provide a reason for cancellation'),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancellation_reason' => $data['cancellation_reason'],
                        'cancelled_by' => 'admin',
                    ]);
                    
                    // Log the action
                    activity()
                        ->performedOn($this->record)
                        ->withProperties(['reason' => $data['cancellation_reason']])
                        ->log('Booking cancelled by admin');
                        
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Actions\Action::make('view_location')
                ->label('View Location')
                ->icon('heroicon-o-map-pin')
                ->color('info')
                ->url(fn (): string => 
                    "https://www.google.com/maps/search/?api=1&query={$this->record->customerAddress->latitude},{$this->record->customerAddress->longitude}"
                )
                ->openUrlInNewTab(),

            Actions\EditAction::make()
                ->visible(fn (): bool => false), // Disable edit for now
        ];
    }
}