<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Update a tenant's subscription plan.
     */
    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'plan' => 'required|in:free,basic,pro,whitelabel',
        ]);

        $oldPlan = $tenant->plan;
        $tenant->plan = $validated['plan'];
        $tenant->save();

        // Log the plan change (optional - for audit trail)
        // Could emit an event here for notifications, billing, etc.

        return response()->json([
            'message' => "Plan updated from {$oldPlan} to {$validated['plan']}",
            'tenant' => $tenant,
        ]);
    }
}
