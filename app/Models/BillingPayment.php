<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'square_payment_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'last_four',
        'card_brand',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice()
    {
        return $this->belongsTo(BillingInvoice::class, 'invoice_id');
    }
}
