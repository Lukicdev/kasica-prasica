<?php

namespace Database\Seeders;

use App\Actions\Demo\SeedSpreadsheetMonth;
use App\Models\User;
use App\Support\CalendarMonth;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * The spreadsheet month is loaded into the current calendar month so the
     * overview shows it as soon as you sign in.
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

        app(SeedSpreadsheetMonth::class)->handle($admin, $partner, CalendarMonth::current());
    }
}
