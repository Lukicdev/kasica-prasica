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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3);
            $table->decimal('account_amount', 15, 2);
            $table->decimal('base_amount', 15, 2);
            $table->decimal('exchange_rate', 18, 8);
            $table->date('booked_on');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->foreignId('category_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('destination_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->decimal('destination_amount', 15, 2)->nullable();
            $table->boolean('repeats')->default(false);
            $table->foreignId('recurrence_id')->nullable()->constrained('transactions')->nullOnDelete();

            $table->index(['account_id', 'booked_on']);
            $table->index(['workspace_id', 'booked_on']);
            $table->index(['workspace_id', 'repeats', 'booked_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
