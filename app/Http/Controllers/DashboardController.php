<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Dashboard\GetDashboardDataAction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(Request $request, GetDashboardDataAction $action): Response
    {
        $data = $action->handle($request->user());

        return Inertia::render('dashboard', $data);
    }
}
