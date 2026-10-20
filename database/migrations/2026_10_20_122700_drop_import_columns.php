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
        if (Schema::hasColumn('accounts', 'is_imported_history')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropIndex(['workspace_id', 'is_imported_history']);
                $table->dropColumn('is_imported_history');
            });
        }

        if (Schema::hasColumn('transactions', 'is_imported')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropIndex(['workspace_id', 'is_imported', 'booked_on']);
                $table->dropColumn('is_imported');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('accounts', 'is_imported_history')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->boolean('is_imported_history')->default(false);
                $table->index(['workspace_id', 'is_imported_history']);
            });
        }

        if (! Schema::hasColumn('transactions', 'is_imported')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->boolean('is_imported')->default(false);
                $table->index(['workspace_id', 'is_imported', 'booked_on']);
            });
        }
    }
};
