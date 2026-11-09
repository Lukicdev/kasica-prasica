<?php

namespace App\Models;

use App\Enums\AssetType;
use App\Enums\Valuation;
use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Something the household owns that is not an account. The value is entered
 * by hand while valuation is manual. A later valuation can replace how the
 * value is set, for example a market price for stocks or crypto.
 *
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property string|null $icon
 * @property AssetType $type
 * @property string $currency
 * @property string $value
 * @property Carbon $valued_on
 * @property Valuation $valuation
 * @property string|null $note
 * @property Carbon|null $archived_at
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
    'name',
    'icon',
    'type',
    'currency',
    'value',
    'valued_on',
    'valuation',
    'note',
    'archived_at',
    'created_by',
    'updated_by',
])]
class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'valuation' => 'manual',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AssetType::class,
            'value' => 'decimal:2',
            'valued_on' => 'date',
            'valuation' => Valuation::class,
            'archived_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
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
