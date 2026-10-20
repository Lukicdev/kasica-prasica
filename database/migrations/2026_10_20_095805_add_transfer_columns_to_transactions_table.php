<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('destination_account_id')
                ->nullable()
                ->after('account_id')
                ->constrained('accounts')
                ->restrictOnDelete();
            $table->decimal('destination_amount', 15, 2)->nullable()->after('account_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('destination_account_id');
            $table->dropColumn('destination_amount');
        });
    }
};
