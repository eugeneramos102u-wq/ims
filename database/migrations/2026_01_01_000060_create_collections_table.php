<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('or_number', 30)->unique();
            $table->foreignId('invoice_id')->constrained('sales_invoices');
            $table->foreignId('customer_id')->constrained('customers');
            $table->date('collection_date');
            $table->decimal('amount', 18, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'check', 'card'])
                ->default('cash');
            $table->string('reference_number', 80)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('collected_by')->constrained('users');
            $table->timestamps();
            $table->index('collection_date');
            $table->index(['invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
