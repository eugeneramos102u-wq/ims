<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adjustment_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('type_name', 60);
            $table->enum('direction', ['IN', 'OUT', 'BOTH']);
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjustment_types');
    }
};
