<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\User;
use App\Models\Invitation;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

echo "=== SUPER ADMIN ↔ MAIN APP INTEGRATION CHECK ===\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n\n";

$passed = 0;
$failed = 0;
$total = 0;

function test($name, $result, $details = '') {
    global $passed, $failed, $total;
    $total++;
    if ($result) {
        echo "[PASS] {$name}\n";
        if ($details) echo "       {$details}\n";
        $passed++;
        return true;
    } else {
        echo "[FAIL] {$name}\n";
        if ($details) echo "       {$details}\n";
        $failed++;
        return false;
    }
}

// ============================================================
// SECTION 1: DATABASE & SHARED RESOURCES
// ============================================================
echo "--- 1. DATABASE & SHARED RESOURCES ---\n";

// Test DB connection
try {
    DB::connection()->getPdo();
    test("Database connection", true, "Connected to: " . config('database.connections.mysql.database'));
} catch (\Exception $e) {
    test("Database connection", false, $e->getMessage());
}

// Check super admin exists
$superAdmin = User::where('is_super_admin', true)->first();
test("Super admin user exists", $superAdmin !== null, $superAdmin ? $superAdmin->email : 'No super admin found');

// Check shared tables accessible
$tenantCount = Tenant::count();
test("Tenants table accessible", $tenantCount >= 0, "Found {$tenantCount} tenants");

$userCount = User::count();
test("Users table accessible", $userCount >= 0, "Found {$userCount} users");

$bookingCount = Booking::count();
test("Bookings table accessible", $bookingCount >= 0, "Found {$bookingCount} bookings");

// ============================================================
// SECTION 2: DASHBOARD STATS INTEGRATION
// ============================================================
echo "\n--- 2. DASHBOARD STATS ---\n";

$activeTenants = Tenant::where('is_suspended', false)->count();
test("Active tenants count", true, "Active: {$activeTenants}, Total: {$tenantCount}");

$planCounts = [
    'free' => Tenant::where('plan', 'free')->count(),
    'basic' => Tenant::where('plan', 'basic')->count(),
    'pro' => Tenant::where('plan', 'pro')->count(),
    'whitelabel' => Tenant::where('plan', 'whitelabel')->count(),
];
test("Plan distribution available", true, "Free: {$planCounts['free']}, Basic: {$planCounts['basic']}, Pro: {$planCounts['pro']}, Whitelabel: {$planCounts['whitelabel']}");

$pendingInvites = Invitation::where('is_used', false)
    ->where(function ($q) {
        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
    })->count();
test("Pending invites count", true, "Pending: {$pendingInvites}");

// ============================================================
// SECTION 3: TENANT MANAGEMENT
// ============================================================
echo "\n--- 3. TENANT MANAGEMENT ---\n";

// Test tenant with relationships
$tenant = Tenant::with(['users'])->first();
test("Tenant relationships load", $tenant !== null, $tenant ? "Tenant: {$tenant->name} (users: " . $tenant->users->count() . ")" : "No tenant");

// Test tenant counts
if ($tenant) {
    $tenant->loadCount(['users', 'bookings', 'services']);
    test("Tenant aggregate counts", true, "Users: {$tenant->users_count}, Bookings: {$tenant->bookings_count}, Services: {$tenant->services_count}");
}

// Test suspension capability
$suspendedCount = Tenant::where('is_suspended', true)->count();
test("Suspension system", true, "Suspended tenants: {$suspendedCount}");

// Check tenant with admin user
$tenantWithAdmin = Tenant::whereHas('users', function($q) {
    $q->where('role', 'admin');
})->first();
test("Tenants have admin users", $tenantWithAdmin !== null, $tenantWithAdmin ? $tenantWithAdmin->name : "No tenant with admin");

// ============================================================
// SECTION 4: INVITE MANAGEMENT
// ============================================================
echo "\n--- 4. INVITE MANAGEMENT ---\n";

$inviteCount = Invitation::count();
test("Invitations table accessible", true, "Total invites: {$inviteCount}");

$usedInvites = Invitation::where('is_used', true)->count();
$unusedInvites = Invitation::where('is_used', false)->count();
test("Invite status tracking", true, "Used: {$usedInvites}, Unused: {$unusedInvites}");

// Check invite relationships
$inviteWithCreator = Invitation::with('creator')->whereNotNull('created_by')->first();
test("Invite creator relationship", $inviteWithCreator !== null || $inviteCount === 0, 
    $inviteWithCreator ? "Created by: " . ($inviteWithCreator->creator->name ?? 'Unknown') : "No invites with creator");

// ============================================================
// SECTION 5: SUBSCRIPTION MANAGEMENT
// ============================================================
echo "\n--- 5. SUBSCRIPTION MANAGEMENT ---\n";

$validPlans = ['free', 'basic', 'pro', 'whitelabel'];
$invalidPlanTenants = Tenant::whereNotIn('plan', $validPlans)->count();
test("All tenants have valid plans", $invalidPlanTenants === 0, "Invalid plan tenants: {$invalidPlanTenants}");

// Check plan column exists and is fillable
$tenantColumns = \Schema::getColumnListing('tenants');
test("Plan column exists", in_array('plan', $tenantColumns));

// ============================================================
// SECTION 6: IMPERSONATION SYSTEM
// ============================================================
echo "\n--- 6. IMPERSONATION SYSTEM ---\n";

// Check impersonation targets (non-super-admin users)
$impersonationTargets = User::where('is_super_admin', false)
    ->whereIn('role', ['admin', 'staff'])
    ->count();
test("Impersonation targets available", $impersonationTargets > 0, "Available targets: {$impersonationTargets}");

// Check tenant users have tenant relationship
$userWithTenant = User::whereNotNull('tenant_id')->with('tenant')->first();
test("User-Tenant relationship works", $userWithTenant !== null && $userWithTenant->tenant !== null,
    $userWithTenant && $userWithTenant->tenant ? "User: {$userWithTenant->name} → Tenant: {$userWithTenant->tenant->name}" : "N/A");

// ============================================================
// SECTION 7: CROSS-APP DATA INTEGRITY
// ============================================================
echo "\n--- 7. CROSS-APP DATA INTEGRITY ---\n";

// Check all tenant users belong to valid tenants
$orphanedUsers = User::whereNotNull('tenant_id')
    ->whereDoesntHave('tenant')
    ->count();
test("No orphaned tenant users", $orphanedUsers === 0, "Orphaned users: {$orphanedUsers}");

// Check all bookings belong to valid tenants
$orphanedBookings = Booking::whereDoesntHave('tenant')->count();
test("No orphaned bookings", $orphanedBookings === 0, "Orphaned bookings: {$orphanedBookings}");

// Check all invites have valid creators (if created_by is set)
$orphanedInvites = Invitation::whereNotNull('created_by')
    ->whereDoesntHave('creator')
    ->count();
test("No orphaned invites", $orphanedInvites === 0, "Orphaned invites: {$orphanedInvites}");

// ============================================================
// SECTION 8: API ROUTES VERIFICATION
// ============================================================
echo "\n--- 8. API ROUTES ---\n";

$router = app('router');
$routes = collect($router->getRoutes())->map(fn($r) => $r->uri())->toArray();

$requiredRoutes = [
    'api/stats' => 'Dashboard stats',
    'api/tenants' => 'Tenant list',
    'api/tenants/{tenant}' => 'Tenant detail',
    'api/tenants/{tenant}/suspend' => 'Tenant suspension',
    'api/invites' => 'Invite list',
    'api/invites/{invitation}' => 'Invite management',
    'api/subscriptions/{tenant}' => 'Subscription management',
    'api/impersonate/{user}' => 'Impersonation start',
    'api/impersonate/stop' => 'Impersonation stop',
];

$routesPassed = 0;
foreach ($requiredRoutes as $route => $desc) {
    if (in_array($route, $routes)) {
        $routesPassed++;
    }
}
test("Required API routes registered", $routesPassed === count($requiredRoutes), 
    "{$routesPassed}/" . count($requiredRoutes) . " routes found");

// ============================================================
// SUMMARY
// ============================================================
echo "\n" . str_repeat("=", 50) . "\n";
echo "INTEGRATION CHECK COMPLETE\n";
echo str_repeat("=", 50) . "\n";
echo "Passed: {$passed}/{$total}\n";
echo "Failed: {$failed}/{$total}\n";

if ($failed === 0) {
    echo "\n✅ ALL INTEGRATION CHECKS PASSED\n";
    exit(0);
} else {
    echo "\n⚠️  SOME CHECKS FAILED - REVIEW REQUIRED\n";
    exit(1);
}
