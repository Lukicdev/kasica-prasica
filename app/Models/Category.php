<?php

namespace App\Models;

use App\Enums\CategoryKind;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An income or expense category. Archived categories stay on past transactions
 * and are left out of the lists used to pick a category.
 *
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property CategoryKind $kind
 * @property string|null $description
 * @property int $position
 * @property Carbon|null $archived_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $transactions_count
 * @property int|null $budgets_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Workspace $workspace
 * @property-read User|null $creator
 * @property-read User|null $editor
 */
#[Fillable([
    'workspace_id',
    'name',
    'kind',
    'description',
    'position',
    'archived_at',
    'created_by',
    'updated_by',
])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => CategoryKind::class,
            'position' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * Categories offered when picking one for a transaction.
     *
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeSelectable(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    /**
     * The text shown when picking this category. The description is part of
     * the label so a short name like A1 stays clear.
     */
    public function choiceLabel(): string
    {
        if ($this->description === null || $this->description === '') {
            return $this->name;
        }

        return $this->name.': '.$this->description;
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return HasMany<CategoryBudget, $this>
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(CategoryBudget::class);
    }

    /**
     * A category that already has transactions or a budget is history.
     * It can be archived, and it cannot be deleted or change kind.
     */
    public function isInUse(): bool
    {
        return $this->transactions()->exists() || $this->budgets()->exists();
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
