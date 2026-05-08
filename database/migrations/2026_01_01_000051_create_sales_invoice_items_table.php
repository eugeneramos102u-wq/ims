<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignId('so_item_id')->nullable()
                ->constrained('sales_order_items')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('qty_invoiced', 12, 2);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->decimal('tax_rate_pct', 5, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->timestamps();
            $table->index(['invoice_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_items');
    }
};
