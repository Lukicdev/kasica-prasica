<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\TransactionType;
use App\Support\Decimal;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One income, expense, transfer, or balance adjustment.
 *
 * The original amount keeps its currency. account_amount is the movement in
 * the source account's currency. A transfer also stores destination_amount in
 * the destination account's currency. base_amount is the dinar value at the
 * rate stored on the row. Adjustments store a signed account_amount.
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $account_id
 * @property int|null $destination_account_id
 * @property int|null $category_id
 * @property int|null $person_id
 * @property TransactionType $type
 * @property string $amount
 * @property Currency $currency
 * @property string $account_amount
 * @property string|null $destination_amount
 * @property string $base_amount
 * @property string $exchange_rate
 * @property Carbon $booked_on
 * @property string|null $note
 * @property bool $repeats
 * @property int|null $recurrence_id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Workspace $workspace
 * @property-read Account $account
 * @property-read Account|null $destinationAccount
 * @property-read Category|null $category
 * @property-read User|null $person
 * @property-read User|null $creator
 * @property-read User|null $editor
 * @property-read Transaction|null $recurrence
 * @property-read Collection<int, Transaction> $occurrences
 */
#[Fillable([
    'workspace_id',
    'account_id',
    'destination_account_id',
    'category_id',
    'person_id',
    'type',
    'amount',
    'currency',
    'account_amount',
    'destination_amount',
    'base_amount',
    'exchange_rate',
    'booked_on',
    'note',
    'repeats',
    'recurrence_id',
    'created_by',
    'updated_by',
])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'repeats' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'currency' => Currency::class,
            'amount' => 'decimal:2',
            'account_amount' => 'decimal:2',
            'destination_amount' => 'decimal:2',
            'base_amount' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'booked_on' => 'date',
            'repeats' => 'boolean',
        ];
    }

    /**
     * Income and expenses marked to appear again each month.
     *
     * @param  Builder<Transaction>  $query
     * @return Builder<Transaction>
     */
    public function scopeRepeating(Builder $query): Builder
    {
        return $query->where('repeats', true);
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'destination_account_id');
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
    public function person(): BelongsTo
    {
        return $this->belongsTo(User::class, 'person_id');
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

    /**
     * The transaction this confirmed entry was copied from.
     *
     * @return BelongsTo<Transaction, $this>
     */
    public function recurrence(): BelongsTo
    {
        return $this->belongsTo(self::class, 'recurrence_id');
    }

    /**
     * Confirmed copies of a repeating transaction.
     *
     * @return HasMany<Transaction, $this>
     */
    public function occurrences(): HasMany
    {
        return $this->hasMany(self::class, 'recurrence_id');
    }

    /**
     * A refund is a negative expense. It reduces spending and increases the balance.
     */
    public function isRefund(): bool
    {
        return $this->type === TransactionType::Expense
            && Decimal::of((string) $this->amount)->isNegative();
    }

    public function typeLabel(): string
    {
        if ($this->isRefund()) {
            return 'Refund';
        }

        return $this->type->label();
    }
}
