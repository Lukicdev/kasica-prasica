<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetOverviewTool;
use Laravel\Mcp\Enums\CacheScope;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Cacheable;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;

#[Name('Kasica')]
#[Version('1.0.0')]
#[Cacheable(ttlMs: 0, scope: CacheScope::Private)]
#[Instructions('Private household budget for the authenticated member. get_overview returns that member\'s workspace month overview: income, spending, category budgets, savings rate, balance, and the daily balance flow.')]
class KasicaServer extends Server
{
    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        GetOverviewTool::class,
    ];
}
