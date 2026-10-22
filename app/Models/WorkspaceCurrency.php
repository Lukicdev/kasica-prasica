<?php

namespace App\Models;

use Database\Factories\WorkspaceCurrencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A currency the household can enter and display.
 *
 * manual_rate is units of the workspace base per one unit of this currency.
 * Null means the rate comes from the stored Frankfurter quotes.
 *
 * @property int $id
 * @property int $workspace_id
 * @property string $code
 * @property string|null $manual_rate
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Workspace $workspace
 */
#[Fillable(['workspace_id', 'code', 'manual_rate'])]
class WorkspaceCurrency extends Model
{
    /** @use HasFactory<WorkspaceCurrencyFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'manual_rate' => 'decimal:8',
        ];
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
