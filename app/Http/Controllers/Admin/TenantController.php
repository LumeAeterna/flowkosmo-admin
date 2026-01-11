<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    /**
     * Create a new tenant with initial admin user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:100|unique:tenants,slug',
            'domain' => 'nullable|string|max:255|unique:tenants,domain',
            'plan' => 'required|in:free,basic,pro,whitelabel',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:8',
        ]);

        // Generate slug from name if not provided
        $slug = $validated['slug'] ?? Str::slug($validated['name']);
        
        // Ensure slug is unique
        $originalSlug = $slug;
        $counter = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        // Create tenant
        $tenant = Tenant::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'domain' => $validated['domain'] ?? null,
            'plan' => $validated['plan'],
            'is_suspended' => false,
        ]);

        // Create admin user for this tenant
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['admin_name'],
            'email' => $validated['admin_email'],
            'password' => Hash::make($validated['admin_password']),
            'role' => 'admin',
        ]);

        return response()->json([
            'message' => 'Tenant created successfully',
            'tenant' => $tenant,
            'admin_user' => $user,
        ], 201);
    }

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
              ->select('id', 'tenant_id', 'name', 'email', 'role', 'created_at', 'email_verified_at');
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
     * Force verify a user's email.
     */
    public function verifyUserEmail(Request $request, Tenant $tenant, User $user)
    {
        // Ensure user belongs to tenant
        if ($user->tenant_id !== $tenant->id) {
            return response()->json(['error' => 'User does not belong to this tenant'], 404);
        }

        $user->email_verified_at = now();
        $user->save();

        return response()->json(['message' => 'Email verified successfully', 'user' => $user]);
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
     * Update a specific user within a tenant.
     */
    public function updateUser(Request $request, Tenant $tenant, User $user)
    {
        // Ensure user belongs to tenant
        if ($user->tenant_id !== $tenant->id) {
            return response()->json(['error' => 'User does not belong to this tenant'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json($user);
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
