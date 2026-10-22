<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Support\CalendarMonth;
use App\Support\OverviewCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OverviewController extends Controller
{
    public function __construct(private OverviewCatalog $catalog) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Transaction::class);

        return Inertia::render('overview/index', $this->catalog->page(
            $request->user(),
            CalendarMonth::fromQuery($request->string('month')->toString()),
        ));
    }
}
