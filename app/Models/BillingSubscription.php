<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'square_subscription_id',
        'square_customer_id',
        'plan',
        'status',
        'amount',
        'currency',
        'billing_cycle',
        'current_period_start',
        'current_period_end',
        'trial_ends_at',
        'cancelled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoices()
    {
        return $this->hasMany(BillingInvoice::class, 'subscription_id');
    }

    public function isActive()
    {
        return in_array($this->status, ['active', 'trialing']);
    }
}
