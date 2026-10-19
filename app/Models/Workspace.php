<?php

namespace App\Models;

use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name'])]
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

        return static::query()->create([
            'name' => self::DEFAULT_NAME,
        ]);
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
     * @return HasOne<User, $this>
     */
    public function admin(): HasOne
    {
        return $this->hasOne(User::class)->where('is_admin', true);
    }
}
