<?php

namespace App\Models;

use App\Actions\Categories\SeedDefaultCategories;
use App\Observers\WorkspaceObserver;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $base_currency
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'base_currency'])]
#[ObservedBy(WorkspaceObserver::class)]
class Workspace extends Model
{
    public const string DEFAULT_NAME = 'Household';

    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory;

    /**
     * The single shared workspace, created the first time it is needed.
     */
    public static function shared(): self
    {
        $workspace = static::query()->first();

        if ($workspace instanceof self) {
            return $workspace;
        }

        $workspace = static::factory()->household()->create([
            'name' => self::DEFAULT_NAME,
        ]);

        // Model events are off while seeding, so the observer does not run then.
        if ($workspace->categories()->doesntExist()) {
            app(SeedDefaultCategories::class)->handle($workspace);
        }

        $workspace->ensureBaseCurrency();

        return $workspace;
    }

    public function isBase(string $currency): bool
    {
        return $this->base_currency === strtoupper($currency);
    }

    public function ensureBaseCurrency(): void
    {
        $this->currencies()->firstOrCreate(
            ['code' => $this->base_currency],
            ['manual_rate' => null],
        );
    }

    public function hasLedger(): bool
    {
        return $this->transactions()->exists()
            || $this->budgetMonths()->whereHas('lines')->exists();
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Account, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * @return HasMany<WorkspaceCurrency, $this>
     */
    public function currencies(): HasMany
    {
        return $this->hasMany(WorkspaceCurrency::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return HasMany<BudgetMonth, $this>
     */
    public function budgetMonths(): HasMany
    {
        return $this->hasMany(BudgetMonth::class);
    }

    /**
     * @return HasOne<User, $this>
     */
    public function admin(): HasOne
    {
        return $this->hasOne(User::class)->where('is_admin', true);
    }
}
