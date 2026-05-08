<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->string('bank_name', 100)->nullable()->after('reference_number');
            $table->string('check_number', 50)->nullable()->after('bank_name');
            $table->date('check_date')->nullable()->after('check_number');
            $table->string('card_last4', 4)->nullable()->after('check_date');
            $table->string('approval_code', 50)->nullable()->after('card_last4');
            $table->index('check_date');
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropIndex(['check_date']);
            $table->dropColumn(['bank_name', 'check_number', 'check_date', 'card_last4', 'approval_code']);
        });
    }
};
