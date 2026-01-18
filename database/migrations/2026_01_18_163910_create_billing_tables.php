<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Billing Subscriptions - tracks tenant subscription to FlowKosmo plans
        Schema::create('billing_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('square_subscription_id')->nullable();
            $table->string('square_customer_id')->nullable();
            $table->string('plan')->default('free'); // free, basic, pro, whitelabel
            $table->string('status')->default('active'); // active, past_due, cancelled, trialing
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('billing_cycle')->default('monthly'); // monthly, yearly
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
        });

        // Billing Invoices
        Schema::create('billing_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('billing_subscriptions')->onDelete('set null');
            $table->string('square_invoice_id')->nullable();
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('pending'); // pending, paid, failed, refunded
            $table->text('description')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
        });

        // Billing Payments
        Schema::create('billing_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained('billing_invoices')->onDelete('set null');
            $table->string('square_payment_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('completed'); // completed, pending, failed, refunded
            $table->string('payment_method')->nullable(); // card, bank, etc.
            $table->string('last_four')->nullable();
            $table->string('card_brand')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_payments');
        Schema::dropIfExists('billing_invoices');
        Schema::dropIfExists('billing_subscriptions');
    }
};
