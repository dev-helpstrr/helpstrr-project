<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Task;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class LiveTasksChart extends ChartWidget
{
    protected static ?string $heading = 'Tasks Overview - Last 7 Days';

    protected function getData(): array
    {
        $data = Task::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'),
            DB::raw('SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled')
        )
        ->where('created_at', '>=', now()->subDays(7))
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Tasks',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
                [
                    'label' => 'Completed',
                    'data' => $data->pluck('completed')->toArray(),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
                [
                    'label' => 'Cancelled',
                    'data' => $data->pluck('cancelled')->toArray(),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
                ],
            ],
            'labels' => $data->pluck('date')->map(function ($date) {
                return \Carbon\Carbon::parse($date)->format('M d');
            })->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}