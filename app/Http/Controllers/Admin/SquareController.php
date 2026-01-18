<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\BillingSubscription;
use App\Models\BillingInvoice;
use App\Models\BillingPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SquareController extends Controller
{
    private $baseUrl;
    private $accessToken;

    public function __construct()
    {
        $this->baseUrl = config('services.square.environment') === 'production' 
            ? 'https://connect.squareup.com' 
            : 'https://connect.squareupsandbox.com';
        $this->accessToken = config('services.square.access_token');
    }

    /**
     * Get Square connection status.
     */
    public function status()
    {
        $connected = !empty($this->accessToken);
        
        return response()->json([
            'connected' => $connected,
            'environment' => config('services.square.environment', 'sandbox'),
        ]);
    }

    /**
     * Get billing overview for all tenants.
     */
    public function overview()
    {
        $subscriptions = BillingSubscription::with('tenant')
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total_subscriptions' => $subscriptions->count(),
            'active' => $subscriptions->where('status', 'active')->count(),
            'past_due' => $subscriptions->where('status', 'past_due')->count(),
            'cancelled' => $subscriptions->where('status', 'cancelled')->count(),
            'mrr' => $subscriptions->where('status', 'active')->sum(function ($sub) {
                return $sub->billing_cycle === 'yearly' ? $sub->amount / 12 : $sub->amount;
            }),
        ];

        return response()->json([
            'stats' => $stats,
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Get billing details for a specific tenant.
     */
    public function tenantBilling(Tenant $tenant)
    {
        $subscription = BillingSubscription::where('tenant_id', $tenant->id)->first();
        $invoices = BillingInvoice::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        $payments = BillingPayment::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'subscription' => $subscription,
            'invoices' => $invoices,
            'payments' => $payments,
        ]);
    }

    /**
     * Create or update a subscription for a tenant.
     */
    public function createSubscription(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'plan' => 'required|in:free,basic,pro,whitelabel',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        // Define plan pricing
        $pricing = [
            'free' => ['monthly' => 0, 'yearly' => 0],
            'basic' => ['monthly' => 29, 'yearly' => 290],
            'pro' => ['monthly' => 79, 'yearly' => 790],
            'whitelabel' => ['monthly' => 199, 'yearly' => 1990],
        ];

        $amount = $pricing[$validated['plan']][$validated['billing_cycle']];

        // Create or update local subscription record
        $subscription = BillingSubscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan' => $validated['plan'],
                'status' => $amount > 0 ? 'active' : 'active',
                'amount' => $amount,
                'currency' => 'USD',
                'billing_cycle' => $validated['billing_cycle'],
                'current_period_start' => now(),
                'current_period_end' => $validated['billing_cycle'] === 'yearly' 
                    ? now()->addYear() 
                    : now()->addMonth(),
            ]
        );

        // Update tenant plan
        $tenant->update(['plan' => $validated['plan']]);

        // If paid plan and Square is connected, create Square subscription
        if ($amount > 0 && $this->accessToken) {
            try {
                // Create customer in Square if not exists
                if (!$subscription->square_customer_id) {
                    $customer = $this->createSquareCustomer($tenant);
                    if ($customer) {
                        $subscription->square_customer_id = $customer['id'];
                        $subscription->save();
                    }
                }
            } catch (\Exception $e) {
                Log::error('Square subscription creation failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Subscription updated successfully',
            'subscription' => $subscription,
        ]);
    }

    /**
     * Create an invoice for a tenant.
     */
    public function createInvoice(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
            'due_days' => 'nullable|integer|min:1|max:90',
        ]);

        $invoice = BillingInvoice::create([
            'tenant_id' => $tenant->id,
            'invoice_number' => BillingInvoice::generateInvoiceNumber(),
            'amount' => $validated['amount'],
            'tax' => 0,
            'total' => $validated['amount'],
            'currency' => 'USD',
            'status' => 'pending',
            'description' => $validated['description'] ?? 'FlowKosmo Subscription',
            'due_date' => now()->addDays($validated['due_days'] ?? 30),
        ]);

        return response()->json([
            'message' => 'Invoice created successfully',
            'invoice' => $invoice,
        ]);
    }

    /**
     * Record a manual payment.
     */
    public function recordPayment(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'invoice_id' => 'nullable|exists:billing_invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        $payment = BillingPayment::create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $validated['invoice_id'] ?? null,
            'amount' => $validated['amount'],
            'currency' => 'USD',
            'status' => 'completed',
            'payment_method' => $validated['payment_method'] ?? 'manual',
            'metadata' => ['notes' => $validated['notes'] ?? null],
        ]);

        // Mark invoice as paid if linked
        if ($validated['invoice_id']) {
            BillingInvoice::where('id', $validated['invoice_id'])->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Payment recorded successfully',
            'payment' => $payment,
        ]);
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(Tenant $tenant)
    {
        $subscription = BillingSubscription::where('tenant_id', $tenant->id)->first();
        
        if (!$subscription) {
            return response()->json(['error' => 'No subscription found'], 404);
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        // Downgrade tenant to free plan
        $tenant->update(['plan' => 'free']);

        return response()->json([
            'message' => 'Subscription cancelled',
            'subscription' => $subscription,
        ]);
    }

    /**
     * Create a customer in Square.
     */
    private function createSquareCustomer(Tenant $tenant)
    {
        $admin = $tenant->users()->where('role', 'admin')->first();
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/v2/customers', [
            'idempotency_key' => 'tenant_' . $tenant->id . '_' . time(),
            'given_name' => $tenant->name,
            'email_address' => $admin?->email,
            'reference_id' => 'tenant_' . $tenant->id,
        ]);

        if ($response->successful()) {
            return $response->json()['customer'];
        }

        Log::error('Square customer creation failed', $response->json());
        return null;
    }
}
