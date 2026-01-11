<?php

use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Redirect root to dashboard
Route::get('/', function () {
    return redirect('/dashboard');
});

// Dashboard (requires auth + super admin + verified email)
Route::middleware(['auth', 'super_admin', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // API routes for admin panel
    Route::prefix('api')->group(function () {
        Route::get('/stats', [Admin\DashboardController::class, 'index']);
        
        // Tenants
        Route::get('/tenants', [Admin\TenantController::class, 'index']);
        Route::post('/tenants', [Admin\TenantController::class, 'store']);
        Route::get('/tenants/{tenant}', [Admin\TenantController::class, 'show']);
        Route::put('/tenants/{tenant}', [Admin\TenantController::class, 'update']);
        Route::post('/tenants/{tenant}/suspend', [Admin\TenantController::class, 'suspend']);
        Route::put('/tenants/{tenant}/users/{user}', [Admin\TenantController::class, 'updateUser']);
        Route::post('/tenants/{tenant}/users/{user}/verify', [Admin\TenantController::class, 'verifyUserEmail']);
        Route::delete('/tenants/{tenant}', [Admin\TenantController::class, 'destroy']);
        
        // Invites
        Route::get('/invites', [Admin\InviteController::class, 'index']);
        Route::post('/invites', [Admin\InviteController::class, 'store']);
        Route::delete('/invites/{invitation}', [Admin\InviteController::class, 'destroy']);
        
        // Subscriptions
        Route::put('/subscriptions/{tenant}', [Admin\SubscriptionController::class, 'update']);
        
        // Impersonation
        Route::post('/impersonate/{user}', [Admin\ImpersonationController::class, 'start']);
        Route::post('/impersonate/stop', [Admin\ImpersonationController::class, 'stop']);
        Route::get('/impersonate/status', [Admin\ImpersonationController::class, 'status']);
    });
    
    // Inertia pages
    Route::get('/tenants', function () {
        return Inertia::render('Tenants');
    })->name('tenants');
    
    Route::get('/tenants/{id}', function ($id) {
        return Inertia::render('TenantDetail', ['id' => $id]);
    })->name('tenant.detail');
    
    Route::get('/invites', function () {
        return Inertia::render('Invites');
    })->name('invites');

    // Profile (required by Breeze layout)
    Route::get('/profile', function () {
        return Inertia::render('Profile/Edit');
    })->name('profile.edit');
});

// Tenant routes (auth required, but not super_admin)
Route::middleware(['auth'])->group(function () {
    Route::get('/{tenant:slug}/dashboard', function (App\Models\Tenant $tenant) {
        // Authorization check: User must belong to tenant OR be super admin
        if (request()->user()->tenant_id !== $tenant->id && !request()->user()->is_super_admin) {
            abort(403, 'Unauthorized access to this tenant.');
        }
        
        return Inertia::render('TenantDashboard', [
            'tenant' => $tenant
        ]);
    })->name('tenant.dashboard');
});

require __DIR__.'/auth.php';

