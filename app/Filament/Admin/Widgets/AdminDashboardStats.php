<?php

namespace App\Filament\Admin\Widgets;

use App\Models\ServiceProvider;
use App\Models\Task;
use App\Models\Customer;
use App\Models\SPKycDocument;
use App\Models\Issue;
use App\Models\EmergencyAlert;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AdminDashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        // Active Service Providers (online in last 24 hours)
        $activeSPs = ServiceProvider::where('is_active', true)
            ->where('last_seen_at', '>=', $yesterday)
            ->count();

        // Live Tasks (in progress)
        $liveTasks = Task::whereIn('status', ['assigned', 'in_progress', 'travelling'])
            ->count();

        // Pending KYC Documents
        $pendingKYC = SPKycDocument::where('verification_status', 'pending')
            ->count();

        // Daily Earnings
        $dailyEarnings = Task::where('status', 'completed')
            ->whereDate('completed_at', $today)
            ->sum('total_amount');

        // Active Issues
        $activeIssues = Issue::whereIn('status', ['open', 'in_progress'])
            ->count();

        // Emergency Alerts
        $emergencyAlerts = EmergencyAlert::where('status', 'active')
            ->count();

        // Tasks needing reassignment
        $reassignmentQueue = Task::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(15))
            ->count();

        // New customers today
        $newCustomers = Customer::whereDate('created_at', $today)
            ->count();

        return [
            Stat::make('Active Service Providers', $activeSPs)
                ->description('Online in last 24 hours')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Live Tasks', $liveTasks)
                ->description('Currently in progress')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Pending KYC', $pendingKYC)
                ->description('Documents awaiting verification')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('warning'),

            Stat::make('Daily Earnings', '₹' . number_format($dailyEarnings, 2))
                ->description('Today\'s completed tasks')
                ->descriptionIcon('heroicon-m-currency-rupee')
                ->color('success'),

            Stat::make('Active Issues', $activeIssues)
                ->description('Requiring attention')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($activeIssues > 0 ? 'danger' : 'success'),

            Stat::make('Emergency Alerts', $emergencyAlerts)
                ->description('Active safety alerts')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color($emergencyAlerts > 0 ? 'danger' : 'success'),

            Stat::make('Reassignment Queue', $reassignmentQueue)
                ->description('Tasks needing manual assignment')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($reassignmentQueue > 0 ? 'warning' : 'success'),

            Stat::make('New Customers', $newCustomers)
                ->description('Registered today')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}