<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SQLite does not index foreign keys on its own. Balances look up transfers
 * by destination account, pending repeats look up occurrences by recurrence,
 * and category checks count by category: each of these scanned the whole
 * transactions table. The same indexes also serve foreign key checks when an
 * account, category, or transaction is deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['destination_account_id', 'booked_on']);
            $table->index('category_id');
            $table->index(['recurrence_id', 'booked_on']);
        });

        Schema::table('category_budgets', function (Blueprint $table) {
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('category_budgets', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['destination_account_id', 'booked_on']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['recurrence_id', 'booked_on']);
        });
    }
};
