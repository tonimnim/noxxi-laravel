<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\Organizer;
use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = null; // Disable polling for performance
    
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static bool $isLazy = false; // Load immediately for important stats

    protected function getStats(): array
    {
        return [
            $this->getGrossRevenueStat(),
            $this->getNetRevenueStat(),
            $this->getTotalUsersStat(),
            $this->getTotalOrganizersStat(),
        ];
    }
    
    protected function getGrossRevenueStat(): Stat
    {
        // Get total gross revenue (all successful transactions)
        $grossRevenue = Cache::remember('admin.stats.gross_revenue', 300, function () {
            return DB::table('transactions')
                ->where('status', 'success')
                ->sum('amount');
        });
        
        // Get this month's revenue for comparison
        $monthlyRevenue = Cache::remember('admin.stats.monthly_revenue', 300, function () {
            return DB::table('transactions')
                ->where('status', 'success')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount');
        });
        
        // Get last 7 days trend
        $trend = Cache::remember('admin.stats.revenue_trend', 600, function () {
            return DB::table('transactions')
                ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
                ->where('created_at', '>=', now()->subDays(7))
                ->where('status', 'success')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('total')
                ->map(fn ($value) => round($value / 1000)) // Simplify for chart display
                ->toArray();
        });
        
        return Stat::make('Gross Revenue', $this->formatCurrency($grossRevenue))
            ->description('This month: ' . $this->formatCurrency($monthlyRevenue))
            ->descriptionIcon('heroicon-m-currency-dollar')
            ->color('success')
            ->chart($trend)
            ->extraAttributes([
                'class' => 'ring-1 ring-green-200 dark:ring-green-800',
            ]);
    }
    
    protected function getNetRevenueStat(): Stat
    {
        // Calculate net revenue (platform profit after organizer payouts)
        // Assuming 15% platform fee (85% goes to organizers)
        $netRevenue = Cache::remember('admin.stats.net_revenue', 300, function () {
            $grossRevenue = DB::table('transactions')
                ->where('status', 'success')
                ->sum('amount');
            
            // Platform keeps 15% commission
            return $grossRevenue * 0.15;
        });
        
        // Get this month's net revenue
        $monthlyNetRevenue = Cache::remember('admin.stats.monthly_net_revenue', 300, function () {
            $monthlyGross = DB::table('transactions')
                ->where('status', 'success')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount');
            
            return $monthlyGross * 0.15;
        });
        
        // Calculate profit margin
        $profitMargin = 15; // Platform commission percentage
        
        return Stat::make('Net Revenue', $this->formatCurrency($netRevenue))
            ->description("Platform profit ({$profitMargin}% commission)")
            ->descriptionIcon('heroicon-m-banknotes')
            ->color('primary')
            ->extraAttributes([
                'class' => 'ring-1 ring-indigo-200 dark:ring-indigo-800',
            ]);
    }
    
    protected function getTotalUsersStat(): Stat
    {
        // Get total users count
        $totalUsers = Cache::remember('admin.stats.total_users', 600, function () {
            return DB::table('users')->count();
        });
        
        // Get new users this month
        $newUsersThisMonth = Cache::remember('admin.stats.new_users_month', 600, function () {
            return DB::table('users')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
        });
        
        // Get active users (logged in within last 30 days)
        $activeUsers = Cache::remember('admin.stats.active_users_30d', 600, function () {
            return DB::table('users')
                ->where('last_active_at', '>=', now()->subDays(30))
                ->count();
        });
        
        // Calculate activity percentage
        $activityRate = $totalUsers > 0 
            ? round(($activeUsers / $totalUsers) * 100, 1)
            : 0;
        
        return Stat::make('Total Users', number_format($totalUsers))
            ->description("+{$newUsersThisMonth} this month • {$activityRate}% active")
            ->descriptionIcon('heroicon-m-user-group')
            ->color('info')
            ->extraAttributes([
                'class' => 'ring-1 ring-blue-200 dark:ring-blue-800',
            ]);
    }
    
    protected function getTotalOrganizersStat(): Stat
    {
        // Get total organizers count
        $totalOrganizers = Cache::remember('admin.stats.total_organizers', 600, function () {
            return DB::table('organizers')->count();
        });
        
        // Get verified organizers
        $verifiedOrganizers = Cache::remember('admin.stats.verified_organizers', 600, function () {
            return DB::table('organizers')
                ->where('is_verified', true)
                ->count();
        });
        
        // Get active organizers (have events in last 90 days)
        $activeOrganizers = Cache::remember('admin.stats.active_organizers', 600, function () {
            return DB::table('organizers')
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('events')
                        ->whereColumn('events.organizer_id', 'organizers.id')
                        ->where('events.created_at', '>=', now()->subDays(90));
                })
                ->count();
        });
        
        // Calculate verification percentage
        $verificationRate = $totalOrganizers > 0 
            ? round(($verifiedOrganizers / $totalOrganizers) * 100, 1)
            : 0;
        
        return Stat::make('Total Organizers', number_format($totalOrganizers))
            ->description("{$activeOrganizers} active • {$verificationRate}% verified")
            ->descriptionIcon('heroicon-m-building-office')
            ->color('warning')
            ->extraAttributes([
                'class' => 'ring-1 ring-amber-200 dark:ring-amber-800',
            ]);
    }
    
    
    protected function formatCurrency($amount): string
    {
        if ($amount >= 1000000) {
            return 'KES ' . number_format($amount / 1000000, 1) . 'M';
        } elseif ($amount >= 1000) {
            return 'KES ' . number_format($amount / 1000, 1) . 'K';
        }
        
        return 'KES ' . number_format($amount, 0);
    }
}