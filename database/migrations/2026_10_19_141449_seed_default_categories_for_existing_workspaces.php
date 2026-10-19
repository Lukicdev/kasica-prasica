<?php

use App\Actions\Categories\SeedDefaultCategories;
use App\Models\Workspace;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Give workspaces created before categories existed their starting list.
     */
    public function up(): void
    {
        $seed = new SeedDefaultCategories;

        Workspace::query()->orderBy('id')->each(function (Workspace $workspace) use ($seed): void {
            $seed->handle($workspace);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('categories')->whereNull('created_by')->delete();
    }
};
