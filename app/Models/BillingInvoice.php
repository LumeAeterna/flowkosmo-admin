<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'square_invoice_id',
        'invoice_number',
        'amount',
        'tax',
        'total',
        'currency',
        'status',
        'description',
        'due_date',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription()
    {
        return $this->belongsTo(BillingSubscription::class, 'subscription_id');
    }

    public function payments()
    {
        return $this->hasMany(BillingPayment::class, 'invoice_id');
    }

    public static function generateInvoiceNumber()
    {
        $prefix = 'INV-' . date('Ym') . '-';
        $lastInvoice = static::where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();
        
        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        }
        
        return $prefix . '0001';
    }
}
