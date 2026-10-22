<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * How many units of a currency one euro was worth on a given day.
 *
 * @property int $id
 * @property Carbon $quoted_on
 * @property string $currency
 * @property string $per_euro
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['quoted_on', 'currency', 'per_euro'])]
class ExchangeRateQuote extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quoted_on' => 'date',
            'per_euro' => 'decimal:8',
        ];
    }
}
