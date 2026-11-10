<?php

namespace App\Mcp\Tools;

use App\Enums\TransactionType;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[Name('create_income')]
#[Title('Create income')]
#[Description('Records income for today in the authenticated member\'s workspace. Takes an amount, an account id, and an income category id. The amount uses the account currency, and the signed-in member is the person.')]
#[IsOpenWorld(false)]
#[IsDestructive(false)]
#[IsIdempotent(false)]
class CreateIncomeTool extends RecordTransactionTool
{
    protected function type(): TransactionType
    {
        return TransactionType::Income;
    }
}
