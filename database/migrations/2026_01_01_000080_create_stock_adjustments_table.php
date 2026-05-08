<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adj_number', 30)->unique();
            $table->foreignId('adjustment_type_id')->constrained('adjustment_types');
            $table->date('adjustment_date');
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'posted', 'void'])->default('draft');
            $table->text('void_reason')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('posted_by')->nullable()->constrained('users');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users');
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
            $table->index('adjustment_date');
            $table->index(['adjustment_type_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
