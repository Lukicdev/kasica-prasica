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
        Schema::create('exchange_rate_quotes', function (Blueprint $table) {
            $table->id();
            $table->date('quoted_on');
            $table->string('currency', 3);
            $table->decimal('per_euro', 18, 8);
            $table->timestamps();

            $table->unique(['quoted_on', 'currency']);
            $table->index(['currency', 'quoted_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rate_quotes');
    }
};
