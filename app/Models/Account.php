<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\Currency;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A place money sits. The balance is never stored: it is the opening balance
 * plus income and minus expenses booked to the account.
 *
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property AccountType $type
 * @property Currency $currency
 * @property string $opening_balance
 * @property Carbon $opening_balance_on
 * @property string|null $note
 * @property Carbon|null $archived_at
 * @property bool $is_imported_history
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $transactions_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Workspace $workspace
 * @property-read User|null $creator
 * @property-read User|null $editor
 */
#[Fillable([
    'workspace_id',
    'name',
    'type',
    'currency',
    'opening_balance',
    'opening_balance_on',
    'note',
    'archived_at',
    'is_imported_history',
    'created_by',
    'updated_by',
])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_imported_history' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'currency' => Currency::class,
            'opening_balance' => 'decimal:2',
            'opening_balance_on' => 'date',
            'archived_at' => 'datetime',
            'is_imported_history' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Account>  $query
     * @return Builder<Account>
     */
    public function scopeListed(Builder $query): Builder
    {
        return $query->where('is_imported_history', false);
    }

    /**
     * Accounts that can be chosen when recording a new transaction.
     *
     * @param  Builder<Account>  $query
     * @return Builder<Account>
     */
    public function scopeSelectable(Builder $query): Builder
    {
        return $query->listed()->whereNull('archived_at');
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
