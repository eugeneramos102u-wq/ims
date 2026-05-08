<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('so_number', 30)->unique();
            $table->string('dr_number', 30)->nullable()->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('salesperson_id')->nullable()
                ->constrained('salespersons')->nullOnDelete();
            $table->date('order_date');
            $table->date('required_date')->nullable();
            $table->enum('price_type', ['wholesale', 'retail'])->default('retail');
            $table->text('shipping_address')->nullable();
            $table->char('currency_code', 3)->default('PHP');
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('shipping_cost', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('commission_rate_pct', 5, 2)->default(0);
            $table->decimal('commission_amount', 18, 2)->default(0);
            $table->enum('status', ['draft', 'confirmed', 'partial', 'fulfilled', 'cancelled'])
                ->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('confirmed_by')->nullable()->constrained('users');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->index('order_date');
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
