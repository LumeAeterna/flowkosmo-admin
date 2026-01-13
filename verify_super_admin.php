<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "--- Super Admin Verification ---\n";

// 1. DB Connection
try {
    DB::connection()->getPdo();
    echo "[PASS] Database connection successful.\n";
} catch (\Exception $e) {
    echo "[FAIL] Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Initial Admin User Check
$admin = User::where('email', 'admin@flowkosmo.com')->first();
if ($admin) {
    echo "[PASS] Super Admin user exists (ID: {$admin->id}).\n";
} else {
    echo "[FAIL] Super Admin user 'admin@flowkosmo.com' NOT found.\n";
}

// 3. Tenant Check
$tenantCount = Tenant::count();
echo "[INFO] Total Tenants: {$tenantCount}\n";

$latestTenant = Tenant::latest()->first();
if ($latestTenant) {
    echo "[PASS] Latest Tenant: {$latestTenant->name} ({$latestTenant->domain})\n";
    echo "       - Plan: {$latestTenant->plan}\n";
    echo "       - Status: " . ($latestTenant->is_active ? 'Active' : 'Inactive') . "\n";
} else {
    echo "[WARN] No tenants found.\n";
}

// 4. Invite Logic Check (Mock)
// Just verification that the model and method exist
if (method_exists(User::class, 'tenants')) {
    echo "[PASS] User model has 'tenants' relationship.\n";
} else {
    echo "[FAIL] User model missing 'tenants' relationship.\n";
}

echo "--- Verification Complete ---\n";
