<?php

namespace App\Observers;

use App\Actions\Categories\SeedDefaultCategories;
use App\Models\Workspace;

class WorkspaceObserver
{
    public function __construct(private SeedDefaultCategories $seed) {}

    public function created(Workspace $workspace): void
    {
        $this->seed->handle($workspace);
        $workspace->ensureBaseCurrency();
    }
}
