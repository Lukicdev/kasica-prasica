<?php

use App\Enums\TransactionType;
use App\Models\Transaction;
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

test('a transfer leaves one account and arrives in the other, and an adjustment uses its sign', function () {
    $balance = app(AccountBalance::class);
    $transfer = new Transaction([
        'account_id' => 1,
        'destination_account_id' => 2,
        'type' => TransactionType::Transfer,
        'account_amount' => '40.00',
        'destination_amount' => '4700.00',
    ]);
    $adjustment = new Transaction([
        'account_id' => 1,
        'type' => TransactionType::Adjustment,
        'account_amount' => '-12.50',
    ]);

    expect($balance->applyOnAccount('100.00', $transfer, 1))->toBe('60.00')
        ->and($balance->applyOnAccount('10.00', $transfer, 2))->toBe('4710.00')
        ->and($balance->applyOnAccount('60.00', $adjustment, 1))->toBe('47.50');
});
