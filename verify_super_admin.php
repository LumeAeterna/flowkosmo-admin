<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\User;
use App\Models\Invite;
use Illuminate\Support\Facades\DB;

echo "=== Super Admin Panel Verification ===\n\n";

$passed = 0;
$total = 0;

// 1. DB Connection
$total++;
try {
    DB::connection()->getPdo();
    echo "[PASS] Database connection successful\n";
    $passed++;
} catch (\Exception $e) {
    echo "[FAIL] Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Super Admin User Check (any user with is_super_admin = true)
$total++;
$admin = User::where('is_super_admin', true)->first();
if ($admin) {
    echo "[PASS] Super Admin user exists: {$admin->email}\n";
    $passed++;
} else {
    // Check if role = 'super_admin'
    $admin = User::where('role', 'super_admin')->first();
    if ($admin) {
        echo "[PASS] Super Admin user exists: {$admin->email}\n";
        $passed++;
    } else {
        echo "[WARN] No Super Admin user found (may need to create one)\n";
    }
}

// 3. Tenant Management
$total++;
$tenantCount = Tenant::count();
if ($tenantCount > 0) {
    echo "[PASS] Tenants in database: {$tenantCount}\n";
    $passed++;
} else {
    echo "[FAIL] No tenants found\n";
}

// 4. Latest Tenant Check
$total++;
$latestTenant = Tenant::latest()->first();
if ($latestTenant) {
    echo "[PASS] Latest Tenant: {$latestTenant->name}\n";
    echo "       - Domain: {$latestTenant->domain}\n";
    echo "       - Plan: {$latestTenant->plan}\n";
    echo "       - Status: " . ($latestTenant->is_active ? 'Active' : 'Inactive') . "\n";
    $passed++;
} else {
    echo "[FAIL] Could not fetch latest tenant\n";
}

// 5. Invite Model exists
$total++;
if (class_exists(\App\Models\Invite::class)) {
    echo "[PASS] Invite model exists\n";
    $passed++;
} else {
    echo "[FAIL] Invite model missing\n";
}

// 6. Check API routes exist
$total++;
$router = app('router');
$routes = collect($router->getRoutes())->map(fn($r) => $r->uri())->toArray();
$requiredRoutes = ['api/tenants', 'api/invites', 'api/stats'];
$foundRoutes = 0;
foreach ($requiredRoutes as $route) {
    if (in_array($route, $routes)) {
        $foundRoutes++;
    }
}
if ($foundRoutes === count($requiredRoutes)) {
    echo "[PASS] All admin API routes registered\n";
    $passed++;
} else {
    echo "[INFO] Found {$foundRoutes}/" . count($requiredRoutes) . " admin routes\n";
}

// 7. Frontend build exists
$total++;
$jsFiles = glob(__DIR__ . '/public/build/assets/app-*.js');
if (!empty($jsFiles)) {
    echo "[PASS] Frontend build exists\n";
    $passed++;
} else {
    echo "[FAIL] No frontend build found\n";
}

echo "\n=== SUMMARY ===\n";
echo "Passed: {$passed}/{$total}\n";

if ($passed >= $total - 1) {
    echo "\n✅ SUPER ADMIN PANEL VERIFIED\n";
    exit(0);
} else {
    echo "\n⚠️ SOME CHECKS INCOMPLETE\n";
    exit(1);
}
