<?php

use App\Contracts\ExchangeRate;
use App\Enums\Currency;
use App\Support\FixedExchangeRate;
use App\Support\MoneyConverter;
use Carbon\CarbonImmutable;
use Tests\TestCase;

uses(TestCase::class);

test('the workspace has exactly two currencies and dinars are the base', function () {
    expect(Currency::cases())->toBe([Currency::Rsd, Currency::Eur])
        ->and(Currency::base())->toBe(Currency::Rsd)
        ->and(Currency::Rsd->isBase())->toBeTrue()
        ->and(Currency::Eur->isBase())->toBeFalse();
});

test('the fixed rate is one euro to 117.5 dinars', function () {
    $rate = app(ExchangeRate::class);

    expect($rate)->toBeInstanceOf(FixedExchangeRate::class)
        ->and($rate->rsdPerUnit(Currency::Eur))->toBe('117.5')
        ->and($rate->rsdPerUnit(Currency::Eur, CarbonImmutable::parse('2020-01-01')))->toBe('117.5')
        ->and($rate->rsdPerUnit(Currency::Rsd))->toBe('1');
});

test('a euro amount is stored in dinars rounded to two decimals', function (string $euros, string $dinars) {
    expect(app(MoneyConverter::class)->baseAmount($euros, Currency::Eur))->toBe($dinars);
})->with([
    'one euro' => ['1', '117.50'],
    'one cent rounds half up' => ['0.01', '1.18'],
    'extra fraction rounds half up' => ['10.01', '1176.18'],
    'refund' => ['-10.01', '-1176.18'],
]);

test('a dinar amount keeps its value when stored', function () {
    $converter = app(MoneyConverter::class);

    expect($converter->baseAmount('17049.79', Currency::Rsd))->toBe('17049.79')
        ->and($converter->baseAmount('10', Currency::Rsd))->toBe('10.00')
        ->and($converter->baseAmount('1.005', Currency::Rsd))->toBe('1.01')
        ->and($converter->baseAmount('1.004', Currency::Rsd))->toBe('1.00');
});

test('showing dinars in euros divides by the fixed rate', function (string $dinars, string $euros) {
    expect(app(MoneyConverter::class)->displayAmount($dinars, Currency::Eur))->toBe($euros);
})->with([
    'one hundred dinars' => ['100', '0.85'],
    'sheet income total' => ['503354', '4283.86'],
    'sheet spent total' => ['488076.54', '4153.84'],
    'refund' => ['-117.50', '-1.00'],
]);

test('showing dinars in dinars does not change the stored amount', function () {
    expect(app(MoneyConverter::class)->displayAmount('17049.79', Currency::Rsd))->toBe('17049.79');
});

test('a euro total is converted from the dinar total', function () {
    $converter = app(MoneyConverter::class);
    $rows = ['0.40', '0.40', '0.40'];

    $roundedRows = array_map(
        fn (string $amount): string => $converter->displayAmount($amount, Currency::Eur),
        $rows,
    );

    expect($roundedRows)->toBe(['0.00', '0.00', '0.00'])
        ->and($converter->displayTotal($rows, Currency::Eur))->toBe('0.01')
        ->and($converter->displayTotal($rows, Currency::Rsd))->toBe('1.20')
        ->and($converter->displayTotal([], Currency::Eur))->toBe('0.00');
});

test('changing the configured rate changes conversion in that one place', function () {
    config(['money.eur_to_rsd' => '100']);

    expect(app(MoneyConverter::class)->baseAmount('2', Currency::Eur))->toBe('200.00')
        ->and(app(MoneyConverter::class)->displayAmount('200', Currency::Eur))->toBe('2.00');
});

test('an amount keeps its original currency when the display currency matches', function () {
    $shown = app(MoneyConverter::class)->present('10.01', Currency::Eur, '999.99', Currency::Eur);

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
    $shown = app(MoneyConverter::class)->present('10.00', Currency::Eur, '1175.00', Currency::Rsd);

    expect($shown->formatted)->toBe('1,175.00 RSD')
        ->and($shown->originalFormatted)->toBe('10.00 EUR');
});

test('a dinar amount shows the converted euros and the original dinars together', function () {
    $shown = app(MoneyConverter::class)->present('100.00', Currency::Rsd, '100.00', Currency::Eur);

    expect($shown->formatted)->toBe('0.85 EUR')
        ->and($shown->originalFormatted)->toBe('100.00 RSD');
});

test('amounts that are not decimals are rejected', function () {
    expect(fn () => app(MoneyConverter::class)->baseAmount('12.345.6', Currency::Rsd))
        ->toThrow(InvalidArgumentException::class);
});
