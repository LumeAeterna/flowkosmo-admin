<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * List all announcements with pagination.
     */
    public function index(Request $request)
    {
        $query = Announcement::with('creator:id,name')
            ->orderBy('created_at', 'desc');

        if ($request->has('active_only') && $request->active_only) {
            $query->active();
        }

        $announcements = $query->paginate(15);

        return response()->json($announcements);
    }

    /**
     * Create a new announcement.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:5000',
            'type' => 'required|in:info,warning,success,alert',
            'target' => 'required|string|max:50',
            'is_dismissible' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        $announcement = Announcement::create([
            ...$validated,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Announcement created successfully',
            'announcement' => $announcement->load('creator:id,name'),
        ], 201);
    }

    /**
     * Get a single announcement.
     */
    public function show(Announcement $announcement)
    {
        return response()->json($announcement->load('creator:id,name'));
    }

    /**
     * Update an announcement.
     */
    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string|max:5000',
            'type' => 'sometimes|in:info,warning,success,alert',
            'target' => 'sometimes|string|max:50',
            'is_active' => 'sometimes|boolean',
            'is_dismissible' => 'sometimes|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
        ]);

        $announcement->update($validated);

        return response()->json([
            'message' => 'Announcement updated',
            'announcement' => $announcement->load('creator:id,name'),
        ]);
    }

    /**
     * Delete an announcement.
     */
    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return response()->json(['message' => 'Announcement deleted']);
    }

    /**
     * Toggle announcement active status.
     */
    public function toggle(Announcement $announcement)
    {
        $announcement->update(['is_active' => !$announcement->is_active]);

        return response()->json([
            'message' => $announcement->is_active ? 'Announcement activated' : 'Announcement deactivated',
            'announcement' => $announcement,
        ]);
    }

    /**
     * Get active announcements for tenant users (called from main app).
     */
    public function forTenant(Request $request)
    {
        $plan = $request->user()?->tenant?->plan ?? 'free';

        $announcements = Announcement::active()
            ->forTarget($plan)
            ->whereDoesntHave('dismissals', function ($q) use ($request) {
                $q->where('user_id', $request->user()?->id);
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json($announcements);
    }
}
