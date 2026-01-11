<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /**
     * List all tenants with pagination and search.
     */
    public function index(Request $request)
    {
        $query = Tenant::withCount(['users', 'bookings', 'services']);

        // Search by name, slug, or domain
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('domain', 'like', "%{$search}%");
            });
        }

        // Filter by plan
        if ($request->has('plan') && $request->plan) {
            $query->where('plan', $request->plan);
        }

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'suspended') {
                $query->where('is_suspended', true);
            } elseif ($request->status === 'active') {
                $query->where('is_suspended', false);
            }
        }

        $tenants = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($tenants);
    }

    /**
     * Get single tenant details.
     */
    public function show(Tenant $tenant)
    {
        $tenant->load(['users' => function ($q) {
            // Only load admin/staff users, not customers (customers should not be impersonated)
            $q->where('role', '!=', 'customer')
              ->select('id', 'tenant_id', 'name', 'email', 'role', 'created_at');
        }]);
        
        $tenant->loadCount(['bookings', 'services']);
        
        // Add some aggregate stats
        $stats = [
            'total_bookings' => $tenant->bookings()->count(),
            'completed_bookings' => $tenant->bookings()->where('status', 'completed')->count(),
            'total_revenue' => $tenant->bookings()
                ->where('status', 'completed')
                ->join('services', 'bookings.service_id', '=', 'services.id')
                ->sum('services.price'),
        ];

        return response()->json([
            'tenant' => $tenant,
            'stats' => $stats,
        ]);
    }

    /**
     * Update tenant details.
     */
    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:100|unique:tenants,slug,' . $tenant->id,
            'domain' => 'nullable|string|max:255|unique:tenants,domain,' . $tenant->id,
            'plan' => 'sometimes|in:free,basic,pro,whitelabel',
            'branding' => 'nullable|array',
        ]);

        $tenant->update($validated);

        return response()->json($tenant);
    }

    /**
     * Toggle tenant suspension status.
     */
    public function suspend(Tenant $tenant)
    {
        $tenant->is_suspended = !$tenant->is_suspended;
        $tenant->suspended_at = $tenant->is_suspended ? now() : null;
        $tenant->save();

        return response()->json([
            'message' => $tenant->is_suspended ? 'Tenant suspended' : 'Tenant reactivated',
            'tenant' => $tenant,
        ]);
    }

    /**
     * Soft delete a tenant.
     */
    public function destroy(Tenant $tenant)
    {
        // Permanent deletion as requested for removing test tenants
        $tenant->delete();

        return response()->json(['message' => 'Tenant has been permanently deleted']);
    }
}
