<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Colors assigned when the column was added. Later choices are left alone.
     *
     * @var array<string, string>
     */
    private array $categoryColors = [
        'Salary' => 'green',
        'Bonus' => 'amber',
        'Gifts and other income' => 'pink',
        'Mortgage' => 'sky',
        'Utilities & Property Tax' => 'yellow',
        'Internet' => 'blue',
        'Groceries' => 'lime',
        'Dining Out' => 'orange',
        'Debt' => 'violet',
        'Beauty' => 'fuchsia',
        'Transportation' => 'teal',
        'A1 (mobile provider)' => 'blue',
        'Subscriptions' => 'violet',
        'Personal care (Nails/Haircuts)' => 'fuchsia',
        'Shopping' => 'orange',
        'Extra expenses (Gifts, miscellaneous, etc.)' => 'amber',
        'Medical' => 'rose',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->categoryColors as $name => $color) {
            DB::table('categories')->where('name', $name)->whereNull('color')->update(['color' => $color]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->categoryColors as $name => $color) {
            DB::table('categories')->where('name', $name)->where('color', $color)->update(['color' => null]);
        }
    }
};
