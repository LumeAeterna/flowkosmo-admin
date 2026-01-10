<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Booking;
use App\Models\Invitation;
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
            if (preg_match('/MemTotal:\s+(\d+)\s+kB/', $memInfo, $matches)) $ramTotal = $matches[1] / 1024; // MB
            if (preg_match('/MemAvailable:\s+(\d+)\s+kB/', $memInfo, $matches)) $ramFree = $matches[1] / 1024; // MB
        }
        $ramUsage = $ramTotal > 0 ? round((($ramTotal - $ramFree) / $ramTotal) * 100) : 0;
        
        $cpuLoad = sys_getloadavg();
        $cpuUsage = isset($cpuLoad[0]) ? round($cpuLoad[0] * 100 / count(file('/proc/cpuinfo') ? file('/proc/cpuinfo') : [1])) : 0; // Rough estimate per core if possible, or just load
        // Simplified CPU (load average as % of 1 core approx)
        $cpuUsage = min(100, round($cpuLoad[0] * 50)); // Visual approximation

        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');
        $diskUsage = round((($diskTotal - $diskFree) / $diskTotal) * 100);

        // App Data
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('is_suspended', false)->count(),
            'total_users' => User::count(), // Total users in system including tenants
            'total_bookings' => Booking::count(),
            'pending_invites' => Invitation::where('is_used', false)
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                })->count(),
            'plans' => [
                'free' => Tenant::where('plan', 'free')->count(),
                'pro' => Tenant::where('plan', 'pro')->count(),
                'whitelabel' => Tenant::where('plan', 'whitelabel')->count(),
            ],
            
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
            ]
        ];

        return response()->json($stats);
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
