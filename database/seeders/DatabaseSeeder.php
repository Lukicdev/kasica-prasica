<?php

namespace Database\Seeders;

use App\Actions\Demo\SeedHouseholdHistory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Two people share one workspace. Transactions and an expense budget
     * cover the current month and the three before it.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $partner = User::factory()->create([
            'name' => 'Partner',
            'email' => 'partner@example.com',
            'workspace_id' => $admin->workspace_id,
        ]);

        app(SeedHouseholdHistory::class)->handle($admin, $partner);
    }
}
