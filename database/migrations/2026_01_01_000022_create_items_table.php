<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 30)->unique();
            $table->string('item_name', 200);
            $table->foreignId('category_id')->constrained('item_categories');
            $table->foreignId('uom_id')->constrained('units_of_measurement');
            $table->text('description')->nullable();
            $table->string('barcode', 60)->nullable();
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->decimal('wholesale_price', 18, 4)->default(0);
            $table->decimal('retail_price', 18, 4)->default(0);
            $table->decimal('tax_rate_pct', 5, 2)->default(0);
            $table->decimal('qty_on_hand', 12, 2)->default(0);
            $table->decimal('reorder_level', 12, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->index('item_name');
            $table->index('status');
            $table->index('qty_on_hand');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
