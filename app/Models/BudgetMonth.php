<?php

namespace App\Models;

use Database\Factories\BudgetMonthFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A calendar month whose budget has been started, empty or copied.
 *
 * Amounts live on the category lines. A month with no lines is an empty plan.
 *
 * @property int $id
 * @property int $workspace_id
 * @property string $month
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Workspace $workspace
 * @property-read User|null $creator
 * @property-read User|null $editor
 */
#[Fillable([
    'workspace_id',
    'month',
    'created_by',
    'updated_by',
])]
class BudgetMonth extends Model
{
    /** @use HasFactory<BudgetMonthFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return HasMany<CategoryBudget, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(CategoryBudget::class);
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
