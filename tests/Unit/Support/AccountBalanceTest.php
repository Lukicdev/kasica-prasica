<?php

use App\Enums\TransactionType;
use App\Support\AccountBalance;
use Tests\TestCase;

uses(TestCase::class);

test('income adds, expenses subtract, and a refund adds', function () {
    $balance = app(AccountBalance::class);

    $afterIncome = $balance->apply('1000.00', TransactionType::Income, '50.50');
    $afterExpense = $balance->apply($afterIncome, TransactionType::Expense, '20.00');
    $afterRefund = $balance->apply($afterExpense, TransactionType::Expense, '-5.25');

    expect($afterIncome)->toBe('1050.50')
        ->and($afterExpense)->toBe('1030.50')
        ->and($afterRefund)->toBe('1035.75');
});
