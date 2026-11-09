<?php

use App\Enums\Valuation;
use App\Models\Asset;
use App\Models\User;
use App\Models\Workspace;
use App\Money\Decimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests cannot view or change assets', function () {
    $asset = Asset::factory()->create();

    $this->get(route('assets.index'))->assertRedirect(route('login'));
    $this->get(route('assets.create'))->assertRedirect(route('login'));
    $this->get(route('assets.show', $asset))->assertRedirect(route('login'));
    $this->post(route('assets.store'), assetPayload())->assertRedirect(route('login'));
    $this->patch(route('assets.update', $asset), assetPayload())->assertRedirect(route('login'));
    $this->delete(route('assets.destroy', $asset))->assertRedirect(route('login'));
});

test('unverified users cannot view or change assets', function () {
    $user = User::factory()->unverified()->create();
    $asset = Asset::factory()->create();

    $this->actingAs($user)
        ->get(route('assets.index'))
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($user)
        ->post(route('assets.store'), assetPayload())
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($user)
        ->patch(route('assets.update', $asset), assetPayload())
        ->assertRedirect(route('verification.notice'));
});

test('a member can create an asset with a manual value', function () {
    $user = User::factory()->create(['name' => 'Ada']);

    $this->actingAs($user)
        ->post(route('assets.store'), assetPayload([
            'name' => 'Family car',
            'type' => 'vehicle',
            'currency' => 'EUR',
            'value' => '8500',
            'valued_on' => '2026-11-01',
            'icon' => 'car',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('assets.index'));

    $asset = Asset::query()->where('name', 'Family car')->first();

    expect($asset)->not->toBeNull()
        ->and($asset->type->value)->toBe('vehicle')
        ->and($asset->currency)->toBe('EUR')
        ->and(Decimal::of((string) $asset->value)->toString(2))->toBe('8500.00')
        ->and($asset->valued_on->toDateString())->toBe('2026-11-01')
        ->and($asset->valuation)->toBe(Valuation::Manual)
        ->and($asset->icon)->toBe('car')
        ->and($asset->note)->toBe('Bought in 2019')
        ->and($asset->created_by)->toBe($user->id)
        ->and($asset->workspace_id)->toBe($user->workspace_id)
        ->and($asset->archived_at)->toBeNull();

    $this->actingAs($user)
        ->get(route('assets.show', $asset))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('asset.name', 'Family car')
            ->where('asset.currency', 'EUR')
            ->where('asset.value', '8500.00')
            ->where('asset.valuation', 'manual')
            ->where('asset.valuation_label', 'By hand')
            ->where('asset.valued_on_label', '1 Nov 2026')
            ->where('asset.current_value.formatted', '8,500.00 EUR')
            ->where('asset.current_value.negative', false)
            ->where('asset.note', 'Bought in 2019'));
});

test('a value can be zero and a note can be left blank', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('assets.store'), assetPayload([
            'name' => 'Old phone',
            'type' => 'other',
            'value' => '0',
            'note' => '',
        ]))
        ->assertSessionHasNoErrors();

    $asset = Asset::query()->where('name', 'Old phone')->first();

    expect($asset)->not->toBeNull()
        ->and($asset->note)->toBeNull()
        ->and(Decimal::of((string) $asset->value)->toString(2))->toBe('0.00');
});

test('asset details are validated', function (array $overrides, string $field) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('assets.create'))
        ->post(route('assets.store'), assetPayload($overrides))
        ->assertRedirect(route('assets.create'))
        ->assertSessionHasErrors($field);

    expect(Asset::query()->count())->toBe(0);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'unknown type' => [['type' => 'stock'], 'type'],
    'unknown currency' => [['currency' => 'USD'], 'currency'],
    'negative value' => [['value' => '-20.5'], 'value'],
    'too many decimals' => [['value' => '10.555'], 'value'],
    'missing date' => [['valued_on' => ''], 'valued_on'],
    'long note' => [['note' => str_repeat('a', 1001)], 'note'],
    'unknown icon' => [['icon' => 'not-an-icon'], 'icon'],
]);

test('a member can choose an asset icon and clear it later', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('assets.store'), assetPayload(['icon' => 'house']))
        ->assertSessionHasNoErrors();

    $asset = Asset::query()->where('name', 'Family home')->first();

    expect($asset)->not->toBeNull()
        ->and($asset->icon)->toBe('house');

    $this->actingAs($user)
        ->get(route('assets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('groups.0.assets.0.icon', 'house')
            ->where('groups.0.assets.0.name', 'Family home'));

    $this->actingAs($user)
        ->patch(route('assets.update', $asset), assetPayload(['icon' => '']))
        ->assertSessionHasNoErrors();

    expect($asset->refresh()->icon)->toBeNull();
});

test('a member can edit every field and the value stays manual', function () {
    $user = User::factory()->create();
    $asset = Asset::factory()->create([
        'name' => 'Car',
        'currency' => 'RSD',
        'value' => '10.00',
    ]);

    $this->actingAs($user)
        ->patch(route('assets.update', $asset), assetPayload([
            'name' => 'Kitchen table',
            'type' => 'valuable',
            'currency' => 'EUR',
            'value' => '80',
            'valued_on' => '2026-02-01',
            'note' => 'Oak',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('assets.show', $asset));

    $asset->refresh();

    expect($asset->name)->toBe('Kitchen table')
        ->and($asset->type->value)->toBe('valuable')
        ->and($asset->currency)->toBe('EUR')
        ->and(Decimal::of((string) $asset->value)->toString(2))->toBe('80.00')
        ->and($asset->valued_on->toDateString())->toBe('2026-02-01')
        ->and($asset->valuation)->toBe(Valuation::Manual)
        ->and($asset->note)->toBe('Oak')
        ->and($asset->updated_by)->toBe($user->id);
});

test('a member can delete an asset', function () {
    $user = User::factory()->create();
    $asset = Asset::factory()->create(['name' => 'Bike']);

    $this->actingAs($user)
        ->delete(route('assets.destroy', $asset))
        ->assertRedirect(route('assets.index'));

    expect(Asset::query()->whereKey($asset->id)->exists())->toBeFalse();
});

test('archiving keeps the asset on the list and in the total', function () {
    $user = User::factory()->create();
    $asset = Asset::factory()->create([
        'name' => 'Old car',
        'type' => 'vehicle',
        'value' => '1500.00',
    ]);

    $this->actingAs($user)
        ->patch(route('assets.archive', $asset))
        ->assertRedirect(route('assets.show', $asset));

    $this->actingAs($user)
        ->get(route('assets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('groups.0.assets.0.name', 'Old car')
            ->where('groups.0.assets.0.archived', true)
            ->where('groups.0.assets.0.current_value.amount', '1500.00')
            ->where('total.formatted', '1,500.00 RSD'));

    $this->actingAs($user)
        ->patch(route('assets.restore', $asset))
        ->assertRedirect(route('assets.show', $asset));

    expect($asset->refresh()->archived_at)->toBeNull();
});

test('the list groups assets by type and totals them in the display currency', function () {
    $user = User::factory()->create();

    Asset::factory()->create([
        'name' => 'Car',
        'type' => 'vehicle',
        'currency' => 'RSD',
        'value' => '100.00',
    ]);
    Asset::factory()->create([
        'name' => 'Home',
        'type' => 'real_estate',
        'currency' => 'RSD',
        'value' => '50.00',
    ]);
    Asset::factory()->create([
        'name' => 'Flat',
        'type' => 'real_estate',
        'currency' => 'EUR',
        'value' => '10.00',
    ]);
    Asset::factory()->archived()->create([
        'name' => 'Old bike',
        'type' => 'vehicle',
        'value' => '5.00',
    ]);

    $this->actingAs($user)
        ->get(route('assets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('groups', fn ($groups) => count($groups) === 2)
            ->where('groups.0.type', 'real_estate')
            ->where('groups.0.label', 'Real estate')
            ->where('groups.0.assets.0.name', 'Flat')
            ->where('groups.0.assets.0.current_value.formatted', '10.00 EUR')
            ->where('groups.0.assets.0.display_value.formatted', '1,175.00 RSD')
            ->where('groups.0.assets.1.name', 'Home')
            ->where('groups.1.type', 'vehicle')
            ->where('groups.1.label', 'Vehicle')
            ->where('groups.1.assets.0.name', 'Car')
            ->where('groups.1.assets.0.current_value.formatted', '100.00 RSD')
            ->where('groups.1.assets.0.display_value', null)
            ->where('groups.1.assets.1.name', 'Old bike')
            ->where('groups.1.assets.1.archived', true)
            ->where('total.currency', 'RSD')
            ->where('total.formatted', '1,330.00 RSD')
            ->missing('groups.2'));
});

test('the euro total is the dinar total divided once', function () {
    $user = User::factory()->displaysIn('EUR')->create();

    Asset::factory()->create(['name' => 'A', 'value' => '10.00']);
    Asset::factory()->create(['name' => 'B', 'value' => '10.00']);

    $this->actingAs($user)
        ->get(route('assets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('groups.0.assets.0.current_value.formatted', '10.00 RSD')
            ->where('groups.0.assets.1.current_value.formatted', '10.00 RSD')
            ->where('total.currency', 'EUR')
            ->where('total.amount', '0.17')
            ->where('total.formatted', '0.17 EUR'));
});

test('another member sees the same assets and an outsider does not', function () {
    $member = User::factory()->create();
    $asset = Asset::factory()->create(['name' => 'Shared home']);
    $elsewhere = Workspace::factory()->create(['name' => 'Elsewhere']);
    $outsider = User::factory()->create(['workspace_id' => $elsewhere->id]);

    $this->actingAs($member)
        ->get(route('assets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('groups.0.assets.0.name', 'Shared home'));

    $this->actingAs($outsider)
        ->get(route('assets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('groups', []));

    $this->actingAs($outsider)
        ->get(route('assets.show', $asset))
        ->assertForbidden();

    $this->actingAs($outsider)
        ->patch(route('assets.update', $asset), assetPayload())
        ->assertForbidden();

    $this->actingAs($outsider)
        ->patch(route('assets.archive', $asset))
        ->assertForbidden();

    $this->actingAs($outsider)
        ->delete(route('assets.destroy', $asset))
        ->assertForbidden();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function assetPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Family home',
        'type' => 'real_estate',
        'currency' => 'RSD',
        'value' => '150000.00',
        'valued_on' => '2026-10-01',
        'note' => 'Bought in 2019',
    ], $overrides);
}
