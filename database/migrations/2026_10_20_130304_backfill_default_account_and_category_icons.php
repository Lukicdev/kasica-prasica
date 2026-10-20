<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Icons assigned when the column was added. Later choices are left alone.
     *
     * @var array<string, string>
     */
    private array $accountIcons = [
        'cash' => 'banknote',
        'bank' => 'landmark',
        'card' => 'credit-card',
        'savings' => 'piggy-bank',
        'other' => 'wallet',
    ];

    /**
     * @var array<string, string>
     */
    private array $categoryIcons = [
        'Salary' => 'briefcase',
        'Bonus' => 'circle-dollar-sign',
        'Gifts and other income' => 'gift',
        'Mortgage' => 'house',
        'Utilities & Property Tax' => 'zap',
        'Internet' => 'wifi',
        'Groceries' => 'shopping-cart',
        'Dining Out' => 'utensils',
        'Debt' => 'landmark',
        'Beauty' => 'sparkles',
        'Transportation' => 'car',
        'A1 (mobile provider)' => 'smartphone',
        'Subscriptions' => 'repeat',
        'Personal care (Nails/Haircuts)' => 'scissors',
        'Shopping' => 'shopping-bag',
        'Extra expenses (Gifts, miscellaneous, etc.)' => 'package',
        'Medical' => 'heart-pulse',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->accountIcons as $type => $icon) {
            DB::table('accounts')->where('type', $type)->whereNull('icon')->update(['icon' => $icon]);
        }

        foreach ($this->categoryIcons as $name => $icon) {
            DB::table('categories')->where('name', $name)->whereNull('icon')->update(['icon' => $icon]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->accountIcons as $type => $icon) {
            DB::table('accounts')->where('type', $type)->where('icon', $icon)->update(['icon' => null]);
        }

        foreach ($this->categoryIcons as $name => $icon) {
            DB::table('categories')->where('name', $name)->where('icon', $icon)->update(['icon' => null]);
        }
    }
};
