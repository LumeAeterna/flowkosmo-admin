<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'type',
        'target',
        'is_active',
        'is_dismissible',
        'starts_at',
        'ends_at',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_dismissible' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dismissals()
    {
        return $this->hasMany(AnnouncementDismissal::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function scopeForTarget($query, $plan)
    {
        return $query->where(function ($q) use ($plan) {
            $q->where('target', 'all')
              ->orWhere('target', 'plan:' . $plan);
        });
    }
}
