<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->string('term_code', 20)->unique();
            $table->string('term_name', 60);
            $table->unsignedSmallInteger('due_days')->default(0);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->unsignedSmallInteger('discount_days')->default(0);
            $table->enum('applies_to', ['sales', 'purchases', 'both'])->default('both');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_terms');
    }
};
