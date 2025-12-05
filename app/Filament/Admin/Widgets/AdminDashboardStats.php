<?php

namespace App\Filament\Admin\Widgets;

use App\Models\ServiceProvider;
use App\Models\Task;
use App\Models\Customer;
use App\Models\SPKycDocument;
use App\Models\Issue;
use App\Models\EmergencyAlert;
use App\Models\TaskBroadcast;
use App\Models\SPUser;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AdminDashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        // Online Service Providers (currently online)
        $onlineSPs = ServiceProvider::whereHas('spUser', function ($query) {
            $query->where('is_online', true)
                  ->where('last_seen_at', '>=', now()->subMinutes(30));
        })->where('is_active', true)->count();

        // Total Active Service Providers
        $totalActiveSPs = ServiceProvider::where('is_active', true)
            ->where('kyc_verified', true)
            ->count();

        // Live Tasks (currently active)
        $liveTasks = Task::whereIn('status', [
            Task::STATUS_ASSIGNED,
            Task::STATUS_ON_THE_WAY,
            Task::STATUS_ARRIVED,
            Task::STATUS_STARTED,
            Task::STATUS_PAUSED,
            Task::STATUS_RESUMED
        ])->count();

        // Tasks awaiting assignment
        $pendingTasks = Task::whereIn('status', [
            Task::STATUS_REQUESTED,
            Task::STATUS_SEARCHING
        ])->count();

        // Daily Earnings (completed tasks today)
        $dailyEarnings = Task::where('status', Task::STATUS_COMPLETED)
            ->whereDate('completed_at', $today)
            ->sum('final_amount');

        // Weekly Earnings
        $weeklyEarnings = Task::where('status', Task::STATUS_COMPLETED)
            ->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('final_amount');

        // Active Broadcasts (pending responses)
        $activeBroadcasts = TaskBroadcast::where('response', 'pending')
            ->where('expires_at', '>', now())
            ->count();

        // New customers today
        $newCustomers = Customer::whereDate('created_at', $today)
            ->count();

        // Completed tasks today
        $completedToday = Task::where('status', Task::STATUS_COMPLETED)
            ->whereDate('completed_at', $today)
            ->count();

        // Customer satisfaction (average rating today)
        $avgRatingToday = Task::where('status', Task::STATUS_RATED)
            ->whereDate('completed_at', $today)
            ->whereNotNull('customer_rating')
            ->avg('customer_rating');

        // Cancellation rate today
        $cancelledToday = Task::where('status', 'cancelled')
            ->whereDate('cancelled_at', $today)
            ->count();
        
        $totalBookingsToday = Task::whereDate('created_at', $today)->count();
        $cancellationRate = $totalBookingsToday > 0 ? round(($cancelledToday / $totalBookingsToday) * 100, 1) : 0;

        return [
            Stat::make('Online Providers', $onlineSPs . '/' . $totalActiveSPs)
                ->description('Currently online / Total active')
                ->descriptionIcon('heroicon-m-users')
                ->color($onlineSPs > 0 ? 'success' : 'warning'),

            Stat::make('Live Tasks', $liveTasks)
                ->description('Currently in progress')
                ->descriptionIcon('heroicon-m-clock')
                ->color($liveTasks > 0 ? 'info' : 'gray'),

            Stat::make('Pending Tasks', $pendingTasks)
                ->description('Awaiting assignment')
                ->descriptionIcon('heroicon-m-queue-list')
                ->color($pendingTasks > 0 ? 'warning' : 'success'),

            Stat::make('Daily Revenue', '₹' . number_format($dailyEarnings, 0))
                ->description('Today\'s completed tasks')
                ->descriptionIcon('heroicon-m-currency-rupee')
                ->color('success'),

            Stat::make('Active Broadcasts', $activeBroadcasts)
                ->description('Pending provider responses')
                ->descriptionIcon('heroicon-m-radio')
                ->color($activeBroadcasts > 0 ? 'info' : 'gray'),

            Stat::make('Completed Today', $completedToday)
                ->description('Tasks finished today')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Customer Rating', $avgRatingToday ? number_format($avgRatingToday, 1) . '/5' : 'N/A')
                ->description('Average rating today')
                ->descriptionIcon('heroicon-m-star')
                ->color($avgRatingToday >= 4 ? 'success' : ($avgRatingToday >= 3 ? 'warning' : 'danger')),

            Stat::make('Cancellation Rate', $cancellationRate . '%')
                ->description('Today\'s cancellation rate')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($cancellationRate < 10 ? 'success' : ($cancellationRate < 20 ? 'warning' : 'danger')),

            Stat::make('New Customers', $newCustomers)
                ->description('Registered today')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info'),

            Stat::make('Weekly Revenue', '₹' . number_format($weeklyEarnings, 0))
                ->description('This week\'s total')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),
        ];
    }

    protected function getColumns(): int
    {
        return 5; // Increased to accommodate more stats
    }
    
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';
}