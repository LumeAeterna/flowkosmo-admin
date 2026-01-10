<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InviteController extends Controller
{
    /**
     * List all invite codes.
     */
    public function index(Request $request)
    {
        $query = Invitation::with(['creator:id,name,email', 'usedByUser:id,name,email', 'tenant:id,name,slug']);

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'used') {
                $query->where('is_used', true);
            } elseif ($request->status === 'unused') {
                $query->where('is_used', false);
            } elseif ($request->status === 'expired') {
                $query->where('is_used', false)
                      ->whereNotNull('expires_at')
                      ->where('expires_at', '<', now());
            }
        }

        $invites = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($invites);
    }

    /**
     * Generate a new invite code.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|min:4|max:20|alpha_num|unique:invitations,code',
            'email' => 'nullable|email|max:255',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);

        // Use custom code if provided, otherwise generate random
        if (!empty($validated['code'])) {
            $code = strtoupper($validated['code']);
        } else {
            $code = strtoupper(Str::random(8));
            
            // Ensure uniqueness
            while (Invitation::where('code', $code)->exists()) {
                $code = strtoupper(Str::random(8));
            }
        }

        $invitation = Invitation::create([
            'code' => $code,
            'email' => $validated['email'] ?? null,
            'is_used' => false,
            'expires_at' => isset($validated['expires_in_days']) 
                ? now()->addDays($validated['expires_in_days']) 
                : null,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Invite code generated successfully',
            'invitation' => $invitation,
        ], 201);
    }

    /**
     * Revoke (delete) an unused invite.
     */
    public function destroy(Invitation $invitation)
    {
        if ($invitation->is_used) {
            return response()->json([
                'error' => 'Cannot delete an already used invite code'
            ], 400);
        }

        $invitation->delete();

        return response()->json(['message' => 'Invite code revoked']);
    }
}
