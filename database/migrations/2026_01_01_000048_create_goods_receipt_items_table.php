<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignId('po_item_id')->constrained('purchase_order_items');
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('qty_received', 12, 2);
            $table->decimal('qty_accepted', 12, 2);
            $table->decimal('qty_rejected', 12, 2)->default(0);
            $table->text('rejection_reason')->nullable();
            $table->decimal('unit_cost', 18, 4);
            $table->timestamps();
            $table->index(['grn_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};
