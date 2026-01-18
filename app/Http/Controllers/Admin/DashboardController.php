<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Booking;
use App\Models\Invitation;
use App\Models\BillingSubscription;
use App\Models\BillingPayment;
use App\Models\BillingInvoice;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get admin dashboard statistics.
     */
    public function index()
    {
        // System Health
        $ramTotal = 0; 
        $ramFree = 0;
        if (file_exists('/proc/meminfo')) {
            $memInfo = file_get_contents('/proc/meminfo');
            if (preg_match('/MemTotal:\s+(\d+)\s+kB/', $memInfo, $matches)) $ramTotal = $matches[1] / 1024;
            if (preg_match('/MemAvailable:\s+(\d+)\s+kB/', $memInfo, $matches)) $ramFree = $matches[1] / 1024;
        }
        $ramUsage = $ramTotal > 0 ? round((($ramTotal - $ramFree) / $ramTotal) * 100) : 0;
        
        $cpuLoad = sys_getloadavg();
        $cpuUsage = min(100, round($cpuLoad[0] * 50));

        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');
        $diskUsage = round((($diskTotal - $diskFree) / $diskTotal) * 100);

        // Revenue Analytics
        $revenueData = $this->getRevenueAnalytics();

        // App Data
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('is_suspended', false)->count(),
            'total_users' => User::count(),
            'total_bookings' => Booking::count(),
            'pending_invites' => Invitation::where('is_used', false)
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                })->count(),
            'plans' => [
                'free' => Tenant::where('plan', 'free')->count(),
                'basic' => Tenant::where('plan', 'basic')->count(),
                'pro' => Tenant::where('plan', 'pro')->count(),
                'whitelabel' => Tenant::where('plan', 'whitelabel')->count(),
            ],
            
            // Revenue Analytics
            'revenue' => $revenueData,
            
            // System Health
            'system' => [
                'cpu' => $cpuUsage,
                'ram' => $ramUsage,
                'disk' => $diskUsage,
                'uptime' => \Carbon\Carbon::parse(exec('uptime -s'))->diffForHumans(null, true),
            ],

            // Chart Data (Last 6 Months)
            'charts' => [
                'tenants_growth' => $this->getGrowthData(Tenant::class),
                'bookings_activity' => $this->getGrowthData(Booking::class),
                'revenue_trend' => $this->getRevenueChartData(),
            ]
        ];

        return response()->json($stats);
    }

    private function getRevenueAnalytics()
    {
        // Monthly Recurring Revenue (MRR)
        $activeSubscriptions = BillingSubscription::where('status', 'active')->get();
        $mrr = $activeSubscriptions->sum(function ($sub) {
            if ($sub->billing_cycle === 'yearly') {
                return $sub->amount / 12;
            }
            return $sub->amount;
        });

        // Total revenue this month
        $monthStart = now()->startOfMonth();
        $monthlyRevenue = BillingPayment::where('status', 'completed')
            ->where('created_at', '>=', $monthStart)
            ->sum('amount');

        // Total revenue all time
        $totalRevenue = BillingPayment::where('status', 'completed')->sum('amount');

        // Revenue by plan
        $revenueByPlan = BillingSubscription::where('status', 'active')
            ->selectRaw('plan, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('plan')
            ->get()
            ->keyBy('plan')
            ->toArray();

        // Outstanding invoices
        $outstandingInvoices = BillingInvoice::where('status', 'pending')->sum('total');
        $overdueInvoices = BillingInvoice::where('status', 'pending')
            ->where('due_date', '<', now())
            ->sum('total');

        return [
            'mrr' => round($mrr, 2),
            'monthly_revenue' => round($monthlyRevenue, 2),
            'total_revenue' => round($totalRevenue, 2),
            'outstanding' => round($outstandingInvoices, 2),
            'overdue' => round($overdueInvoices, 2),
            'by_plan' => $revenueByPlan,
            'active_subscriptions' => $activeSubscriptions->count(),
        ];
    }

    private function getRevenueChartData()
    {
        $data = [];
        $labels = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M');
            $data[] = BillingPayment::where('status', 'completed')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('amount');
        }
        return ['labels' => $labels, 'data' => $data];
    }

    private function getGrowthData($model)
    {
        $data = [];
        $labels = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M');
            $data[] = $model::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        }
        return ['labels' => $labels, 'data' => $data];
    }
}
