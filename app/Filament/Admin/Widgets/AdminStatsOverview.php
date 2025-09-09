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
    protected static ?string $pollingInterval = '10s'; // Poll every 10 seconds for real-time updates
    
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static bool $isLazy = false; // Load immediately for important stats
    
    // Force 4 columns to display all stats in one horizontal row
    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        return [
            $this->getGrossRevenueStat(),
            $this->getNetRevenueStat(),
            $this->getPendingCommissionStat(),
            $this->getTotalUsersStat(),
            // Removed Total Organizers to fit in one row
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
            ->description(null) // Remove description for compact display
            ->color('success')
            ->chart($trend)
            ->extraAttributes([
                'class' => 'ring-1 ring-green-200 dark:ring-green-800',
            ]);
    }
    
    protected function getNetRevenueStat(): Stat
    {
        // Calculate net revenue from successful payouts only (actual platform profit)
        $netRevenue = Cache::remember('admin.stats.net_revenue', 30, function () {
            // Platform keeps commission only after successful payout to organizer
            // Sum commission from completed/paid payouts only
            return DB::table('payouts')
                ->whereIn('status', ['completed', 'paid'])
                ->sum('commission_amount') ?: 0;
        });
        
        // Get this month's net revenue from completed payouts
        $monthlyNetRevenue = Cache::remember('admin.stats.monthly_net_revenue', 30, function () {
            return DB::table('payouts')
                ->whereIn('status', ['completed', 'paid'])
                ->whereMonth('completed_at', now()->month)
                ->whereYear('completed_at', now()->year)
                ->sum('commission_amount') ?: 0;
        });
        
        // Calculate average commission rate from actual data
        $avgCommissionRate = Cache::remember('admin.stats.avg_commission_rate', 60, function () {
            $result = DB::table('transactions')
                ->where('status', 'success')
                ->whereNotNull('platform_commission')
                ->where('amount', '>', 0)
                ->selectRaw('AVG(platform_commission * 100.0 / amount) as avg_rate')
                ->first();
            
            return $result && $result->avg_rate ? round($result->avg_rate, 1) : 0;
        });
        
        // If no transactions yet, check organizer rates for display
        if ($avgCommissionRate == 0) {
            $defaultRate = DB::table('organizers')
                ->whereNotNull('commission_rate')
                ->avg('commission_rate') ?: 10; // Default fallback
            $avgCommissionRate = round($defaultRate, 1);
        }
        
        return Stat::make('Net Revenue', $this->formatCurrency($netRevenue))
            ->description(null) // Remove description for compact display
            ->color('primary')
            ->extraAttributes([
                'class' => 'ring-1 ring-green-200 dark:ring-green-800',
            ]);
    }
    
    protected function getPendingCommissionStat(): Stat
    {
        // Calculate pending commission (earned but not yet paid out)
        $pendingCommission = Cache::remember('admin.stats.pending_commission', 30, function () {
            // Total commission from all successful transactions
            $totalCommission = DB::table('transactions')
                ->where('status', 'success')
                ->sum('platform_commission') ?: 0;
            
            // Commission already paid out in completed payouts
            $paidCommission = DB::table('payouts')
                ->whereIn('status', ['completed', 'paid'])
                ->sum('commission_amount') ?: 0;
            
            // Pending = Total earned - Already paid
            return max(0, $totalCommission - $paidCommission);
        });
        
        // Count pending payouts
        $pendingPayouts = Cache::remember('admin.stats.pending_payouts_count', 30, function () {
            return DB::table('payouts')
                ->whereIn('status', ['pending', 'approved', 'processing'])
                ->count();
        });
        
        return Stat::make('Pending Commission', $this->formatCurrency($pendingCommission))
            ->description(null) // Remove description for compact display
            ->color('warning')
            ->extraAttributes([
                'class' => 'ring-1 ring-yellow-200 dark:ring-yellow-800',
            ]);
    }
    
    protected function getTotalUsersStat(): Stat
    {
        // Get total users count - NO CACHE for real-time updates
        $totalUsers = DB::table('users')->count();
        
        // Get new users this month - Short cache (30 seconds)
        $newUsersThisMonth = Cache::remember('admin.stats.new_users_month', 30, function () {
            return DB::table('users')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
        });
        
        // Get active users (logged in within last 30 days) - Short cache
        $activeUsers = Cache::remember('admin.stats.active_users_30d', 30, function () {
            return DB::table('users')
                ->where('last_active_at', '>=', now()->subDays(30))
                ->count();
        });
        
        // Calculate activity percentage
        $activityRate = $totalUsers > 0 
            ? round(($activeUsers / $totalUsers) * 100, 1)
            : 0;
        
        return Stat::make('Total Users', number_format($totalUsers))
            ->description(null) // Remove description for compact display
            ->color('info')
            ->extraAttributes([
                'class' => 'ring-1 ring-blue-200 dark:ring-blue-800',
            ]);
    }
    
    protected function getTotalOrganizersStat(): Stat
    {
        // Get total organizers count - NO CACHE for real-time updates
        $totalOrganizers = DB::table('organizers')->count();
        
        // Get verified organizers - Short cache (30 seconds)
        $verifiedOrganizers = Cache::remember('admin.stats.verified_organizers', 30, function () {
            return DB::table('organizers')
                ->where('is_verified', true)
                ->count();
        });
        
        // Get active organizers (have events in last 90 days) - Short cache
        $activeOrganizers = Cache::remember('admin.stats.active_organizers', 30, function () {
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