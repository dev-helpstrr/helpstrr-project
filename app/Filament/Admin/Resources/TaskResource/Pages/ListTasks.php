<?php

namespace App\Filament\Admin\Resources\TaskResource\Pages;

use App\Filament\Admin\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        // return [
        //     Actions\CreateAction::make(),
        // ];
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Tasks'),

            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['requested', 'searching']))
                ->badge(fn () => $this->getModel()::whereIn('status', ['requested', 'searching'])->count()),

            'in_progress' => Tab::make('In Progress')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    'assigned', 'on_the_way', 'arrived', 'otp_start_verified', 'started', 'paused', 'resumed'
                ]))
                ->badge(fn () => $this->getModel()::whereIn('status', [
                    'assigned', 'on_the_way', 'arrived', 'otp_start_verified', 'started', 'paused', 'resumed'
                ])->count()),

            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['completed', 'rated']))
                ->badge(fn () => $this->getModel()::whereIn('status', ['completed', 'rated'])->count()),

            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled'))
                ->badge(fn () => $this->getModel()::where('status', 'cancelled')->count()),

            'today' => Tab::make('Today')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('scheduled_at', today()))
                ->badge(fn () => $this->getModel()::whereDate('scheduled_at', today())->count()),
        ];
    }
}
