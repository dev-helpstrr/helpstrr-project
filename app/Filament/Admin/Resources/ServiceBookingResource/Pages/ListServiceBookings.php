<?php

namespace App\Filament\Admin\Resources\ServiceBookingResource\Pages;

use App\Filament\Admin\Resources\ServiceBookingResource;
use App\Models\Task;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListServiceBookings extends ListRecords
{
    protected static string $resource = ServiceBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->redirect(request()->header('Referer'))),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Bookings')
                ->badge(Task::count()),
            
            'new' => Tab::make('New Bookings')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    Task::STATUS_REQUESTED,
                    Task::STATUS_SEARCHING,
                ]))
                ->badge(Task::whereIn('status', [
                    Task::STATUS_REQUESTED,
                    Task::STATUS_SEARCHING,
                ])->count())
                ->badgeColor('warning'),
            
            'active' => Tab::make('Active Services')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    Task::STATUS_ASSIGNED,
                    Task::STATUS_ON_THE_WAY,
                    Task::STATUS_ARRIVED,
                    Task::STATUS_STARTED,
                    Task::STATUS_PAUSED,
                    Task::STATUS_RESUMED,
                ]))
                ->badge(Task::whereIn('status', [
                    Task::STATUS_ASSIGNED,
                    Task::STATUS_ON_THE_WAY,
                    Task::STATUS_ARRIVED,
                    Task::STATUS_STARTED,
                    Task::STATUS_PAUSED,
                    Task::STATUS_RESUMED,
                ])->count())
                ->badgeColor('primary'),
            
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    Task::STATUS_COMPLETED,
                    Task::STATUS_RATED,
                ]))
                ->badge(Task::whereIn('status', [
                    Task::STATUS_COMPLETED,
                    Task::STATUS_RATED,
                ])->count())
                ->badgeColor('success'),
            
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled'))
                ->badge(Task::where('status', 'cancelled')->count())
                ->badgeColor('danger'),
            
            'today' => Tab::make('Today\'s Bookings')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('scheduled_at', today()))
                ->badge(Task::whereDate('scheduled_at', today())->count())
                ->badgeColor('info'),
        ];
    }
}