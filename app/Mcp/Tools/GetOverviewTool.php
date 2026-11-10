<?php

namespace App\Mcp\Tools;

use App\Catalogs\OverviewCatalog;
use App\Models\Transaction;
use App\Models\User;
use App\Support\CalendarMonth;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_overview')]
#[Title('Get overview')]
#[Description('Returns the household month overview for the authenticated member: income, spending, category budgets, savings rate, balance, and the daily balance flow. Uses the current month when month is omitted.')]
#[IsReadOnly]
#[IsOpenWorld(false)]
class GetOverviewTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, OverviewCatalog $overview): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('Authentication required.');
        }

        $validated = $request->validate([
            'month' => ['sometimes', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        Gate::authorize('viewAny', Transaction::class);

        $month = $validated['month'] ?? '';

        return Response::structured($overview->page(
            $user,
            CalendarMonth::fromQuery(is_string($month) ? $month : ''),
        ));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'month' => $schema->string()
                ->description('Calendar month as YYYY-MM. Omit for the current month.')
                ->pattern('^\d{4}-(0[1-9]|1[0-2])$'),
        ];
    }
}
