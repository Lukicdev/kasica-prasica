<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return Cache::lock('shared-workspace', 10)->block(5, function () use ($input): User {
            return DB::transaction(function () use ($input): User {
                $workspace = Workspace::query()->lockForUpdate()->first();

                if ($workspace === null) {
                    $workspace = Workspace::query()->create([
                        'name' => Workspace::DEFAULT_NAME,
                    ]);
                }

                $user = new User([
                    'name' => $input['name'],
                    'email' => $input['email'],
                    'password' => $input['password'],
                ]);

                $user->workspace()->associate($workspace);
                $user->is_admin = ! User::query()->exists();
                $user->save();

                return $user;
            });
        });
    }
}
