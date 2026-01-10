<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating a user.
     * Stores the original admin's ID in the session.
     */
    public function start(Request $request, User $user)
    {
        // Prevent impersonating another super admin
        if ($user->is_super_admin) {
            return response()->json([
                'error' => 'Cannot impersonate another super admin'
            ], 403);
        }

        // Store original admin ID
        session(['impersonating_from' => $request->user()->id]);
        session(['impersonation_started_at' => now()->toISOString()]);

        // Log the impersonation (for audit trail)
        \Log::info('Impersonation started', [
            'admin_id' => $request->user()->id,
            'admin_email' => $request->user()->email,
            'target_user_id' => $user->id,
            'target_user_email' => $user->email,
        ]);

        // Login as the target user
        Auth::login($user);

        return response()->json([
            'message' => "Now impersonating {$user->name}",
            'user' => $user->only(['id', 'name', 'email', 'tenant_id']),
            'redirect_to' => $user->tenant ? "/{$user->tenant->slug}/dashboard" : '/dashboard',
        ]);
    }

    /**
     * Stop impersonating and return to admin account.
     */
    public function stop(Request $request)
    {
        $originalAdminId = session('impersonating_from');

        if (!$originalAdminId) {
            return response()->json([
                'error' => 'Not currently impersonating anyone'
            ], 400);
        }

        $admin = User::find($originalAdminId);

        if (!$admin || !$admin->is_super_admin) {
            // Clear session and log out for safety
            session()->forget(['impersonating_from', 'impersonation_started_at']);
            Auth::logout();
            return response()->json([
                'error' => 'Original admin account not found',
                'redirect_to' => '/login',
            ], 400);
        }

        // Log the return
        \Log::info('Impersonation ended', [
            'admin_id' => $admin->id,
            'was_impersonating' => $request->user()->id,
        ]);

        // Clear impersonation session
        session()->forget(['impersonating_from', 'impersonation_started_at']);

        // Return to admin
        Auth::login($admin);

        return response()->json([
            'message' => 'Returned to admin account',
            'redirect_to' => '/admin',
        ]);
    }

    /**
     * Check if currently impersonating.
     */
    public function status(Request $request)
    {
        $isImpersonating = session()->has('impersonating_from');
        
        return response()->json([
            'is_impersonating' => $isImpersonating,
            'started_at' => session('impersonation_started_at'),
        ]);
    }
}
