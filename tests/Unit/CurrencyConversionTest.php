<?php

use App\Contracts\ExchangeRate;
use App\Models\Workspace;
use App\Support\MoneyConverter;
use App\Support\StoredExchangeRate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('the demo household keeps dinars as the base and enables euros', function () {
    $workspace = Workspace::shared();

    expect($workspace->base_currency)->toBe('RSD')
        ->and($workspace->isBase('RSD'))->toBeTrue()
        ->and($workspace->isBase('EUR'))->toBeFalse()
        ->and($workspace->currencies()->orderBy('code')->pluck('code')->all())->toBe(['EUR', 'RSD']);
});

test('one euro is worth 117.5 dinars until a newer quote exists', function () {
    $workspace = Workspace::shared();
    $rate = app(ExchangeRate::class);

    expect($rate)->toBeInstanceOf(StoredExchangeRate::class)
        ->and($rate->basePerUnit($workspace, 'EUR'))->toBe('117.5')
        ->and($rate->basePerUnit($workspace, 'EUR', CarbonImmutable::parse('2020-01-01')))->toBe('117.5')
        ->and($rate->basePerUnit($workspace, 'RSD'))->toBe('1');
});

test('a euro amount is stored in dinars rounded to two decimals', function (string $euros, string $dinars) {
    $workspace = Workspace::shared();

    expect(app(MoneyConverter::class)->baseAmount($euros, 'EUR', $workspace))->toBe($dinars);
})->with([
    'one euro' => ['1', '117.50'],
    'one cent rounds half up' => ['0.01', '1.18'],
    'extra fraction rounds half up' => ['10.01', '1176.18'],
    'refund' => ['-10.01', '-1176.18'],
]);

test('a dinar amount keeps its value when stored', function () {
    $workspace = Workspace::shared();
    $converter = app(MoneyConverter::class);

    expect($converter->baseAmount('17049.79', 'RSD', $workspace))->toBe('17049.79')
        ->and($converter->baseAmount('10', 'RSD', $workspace))->toBe('10.00')
        ->and($converter->baseAmount('1.005', 'RSD', $workspace))->toBe('1.01')
        ->and($converter->baseAmount('1.004', 'RSD', $workspace))->toBe('1.00');
});

test('showing dinars in euros divides by the quoted rate', function (string $dinars, string $euros) {
    $workspace = Workspace::shared();

    expect(app(MoneyConverter::class)->displayAmount($dinars, 'EUR', $workspace))->toBe($euros);
})->with([
    'one hundred dinars' => ['100', '0.85'],
    'sheet income total' => ['503354', '4283.86'],
    'sheet spent total' => ['488076.54', '4153.84'],
    'refund' => ['-117.50', '-1.00'],
]);

test('showing dinars in dinars does not change the stored amount', function () {
    $workspace = Workspace::shared();

    expect(app(MoneyConverter::class)->displayAmount('17049.79', 'RSD', $workspace))->toBe('17049.79');
});

test('a euro total is converted from the dinar total', function () {
    $workspace = Workspace::shared();
    $converter = app(MoneyConverter::class);
    $rows = ['0.40', '0.40', '0.40'];

    $roundedRows = array_map(
        fn (string $amount): string => $converter->displayAmount($amount, 'EUR', $workspace),
        $rows,
    );

    expect($roundedRows)->toBe(['0.00', '0.00', '0.00'])
        ->and($converter->displayTotal($rows, 'EUR', $workspace))->toBe('0.01')
        ->and($converter->displayTotal($rows, 'RSD', $workspace))->toBe('1.20')
        ->and($converter->displayTotal([], 'EUR', $workspace))->toBe('0.00');
});

test('a manual rate replaces the quoted rate', function () {
    $workspace = Workspace::shared();
    $workspace->currencies()->where('code', 'EUR')->update(['manual_rate' => '100']);
    $workspace->unsetRelation('currencies');

    expect(app(MoneyConverter::class)->baseAmount('2', 'EUR', $workspace))->toBe('200.00')
        ->and(app(MoneyConverter::class)->displayAmount('200', 'EUR', $workspace))->toBe('2.00');
});

test('an amount keeps its original currency when the display currency matches', function () {
    $workspace = Workspace::shared();
    $shown = app(MoneyConverter::class)->present('10.01', 'EUR', '999.99', 'EUR', $workspace);

    expect($shown->formatted)->toBe('10.01 EUR')
        ->and($shown->originalFormatted)->toBeNull()
        ->and($shown->toArray())->toBe([
            'amount' => '10.01',
            'currency' => 'EUR',
            'formatted' => '10.01 EUR',
            'original_amount' => null,
            'original_currency' => null,
            'original_formatted' => null,
        ]);
});

test('an amount shows the stored dinars and the original euros together', function () {
    $workspace = Workspace::shared();
    $shown = app(MoneyConverter::class)->present('10.00', 'EUR', '1175.00', 'RSD', $workspace);

    expect($shown->formatted)->toBe('1,175.00 RSD')
        ->and($shown->originalFormatted)->toBe('10.00 EUR');
});

test('a dinar amount shows the converted euros and the original dinars together', function () {
    $workspace = Workspace::shared();
    $shown = app(MoneyConverter::class)->present('100.00', 'RSD', '100.00', 'EUR', $workspace);

    expect($shown->formatted)->toBe('0.85 EUR')
        ->and($shown->originalFormatted)->toBe('100.00 RSD');
});

test('amounts that are not decimals are rejected', function () {
    $workspace = Workspace::shared();

    expect(fn () => app(MoneyConverter::class)->baseAmount('12.345.6', 'RSD', $workspace))
        ->toThrow(InvalidArgumentException::class);
});
