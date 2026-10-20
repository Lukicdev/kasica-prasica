<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Support\InsightsCatalog;
use App\Support\InsightsQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InsightsController extends Controller
{
    public function __construct(private InsightsCatalog $catalog) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Transaction::class);

        return Inertia::render('insights/index', $this->catalog->page(
            $request->user(),
            InsightsQuery::fromRequest($request),
        ));
    }
}
