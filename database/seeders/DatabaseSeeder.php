<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Model events stay off for this run. Workspace::shared() still creates
     * the default categories, because the workspace observer does not fire.
     */
    public function run(): void
    {
        $this->call(HouseholdHistorySeeder::class);
    }
}
