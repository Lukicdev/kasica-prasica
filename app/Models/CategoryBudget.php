<?php

namespace App\Models;

use Database\Factories\CategoryBudgetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The dinar budget for one expense category in one month.
 *
 * @property int $id
 * @property int $budget_month_id
 * @property int $category_id
 * @property string $amount
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read BudgetMonth $budgetMonth
 * @property-read Category $category
 * @property-read User|null $creator
 * @property-read User|null $editor
 */
#[Fillable([
    'budget_month_id',
    'category_id',
    'amount',
    'created_by',
    'updated_by',
])]
class CategoryBudget extends Model
{
    /** @use HasFactory<CategoryBudgetFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<BudgetMonth, $this>
     */
    public function budgetMonth(): BelongsTo
    {
        return $this->belongsTo(BudgetMonth::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
