<?php

use App\Models\NfeImport;
use App\Models\Person;
use App\Models\Product;
use App\Models\SefazCertificate;
use App\Models\SefazSyncState;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 10, 23)->setTime(10, 0));
});

test('guests are redirected to the login page', function () {
    $this->get('/')->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/')->assertOk();
});

test('kpis compare the month with the previous one', function () {
    NfeImport::factory()->count(3)->create(['issued_at' => '2026-10-05', 'total' => 100]);
    NfeImport::factory()->count(2)->create(['issued_at' => '2026-09-10', 'total' => 100]);

    $this->actingAs(User::factory()->create())->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('period', 'month')
            ->where('kpis.count.value', 3)
            ->where('kpis.count.change', 50)
            ->where('kpis.total.value', '300.00')
            ->where('kpis.total.change', 50));
});

test('change is null when the previous period has no notes', function () {
    NfeImport::factory()->create(['issued_at' => '2026-10-05']);

    $this->actingAs(User::factory()->create())->get('/')
        ->assertInertia(fn (Assert $page) => $page->where('kpis.count.change', null));
});

test('period filter widens the range', function () {
    NfeImport::factory()->create(['issued_at' => '2026-10-05']);
    NfeImport::factory()->create(['issued_at' => '2026-08-15']);
    NfeImport::factory()->create(['issued_at' => '2026-02-15']);
    $user = User::factory()->create();

    $this->actingAs($user)->get('/?period=3m')
        ->assertInertia(fn (Assert $page) => $page->where('kpis.count.value', 2));

    $this->actingAs($user)->get('/?period=12m')
        ->assertInertia(fn (Assert $page) => $page->where('kpis.count.value', 3));

    $this->actingAs($user)->get('/?period=banana')->assertSessionHasErrors('period');
});

test('counts active and new suppliers and products without sale price', function () {
    $old = Person::factory()->create();
    $new = Person::factory()->create();
    NfeImport::factory()->for($old, 'supplier')->create(['issued_at' => '2026-05-10']);
    NfeImport::factory()->for($old, 'supplier')->create(['issued_at' => '2026-10-02']);
    NfeImport::factory()->for($new, 'supplier')->create(['issued_at' => '2026-10-03']);
    Product::factory()->count(2)->create(['sale_price' => null]);
    Product::factory()->create(['sale_price' => 9]);

    $this->actingAs(User::factory()->create())->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->where('kpis.suppliers.active', 2)
            ->where('kpis.suppliers.new', 1)
            ->where('kpis.products.without_price', 2)
            ->where('kpis.products.total', 3));
});

test('monthly totals cover the last 12 months including empty ones', function () {
    NfeImport::factory()->create(['issued_at' => '2026-10-05', 'total' => 40]);
    NfeImport::factory()->create(['issued_at' => '2026-10-20', 'total' => 60]);
    NfeImport::factory()->create(['issued_at' => '2025-09-30', 'total' => 999]);

    $this->actingAs(User::factory()->create())->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->has('months', 12)
            ->where('months.0.month', '2025-11')
            ->where('months.11.month', '2026-10')
            ->where('months.11.total', '100.00')
            ->where('months.5.total', '0.00'));
});

test('top suppliers are limited to five and the rest is grouped', function () {
    foreach (range(1, 7) as $position) {
        NfeImport::factory()
            ->for(Person::factory(['name' => "Fornecedor {$position}"]), 'supplier')
            ->create(['issued_at' => '2026-10-05', 'total' => 1000 - $position * 100]);
    }

    $this->actingAs(User::factory()->create())->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->has('suppliers', 6)
            ->where('suppliers.0.name', 'Fornecedor 1')
            ->where('suppliers.0.total', '900.00')
            ->where('suppliers.5.name', 'Outros (2)')
            ->where('suppliers.5.total', '700.00'));
});

test('recent imports lists the five latest notes', function () {
    NfeImport::factory()->count(7)->sequence(fn ($sequence) => ['issued_at' => now()->subDays($sequence->index)])->create();

    $this->actingAs(User::factory()->create())->get('/')
        ->assertInertia(fn (Assert $page) => $page->has('recent', 5));
});

test('sync reports whether all documents were fetched and the next scheduled run', function () {
    SefazSyncState::current()->update(['last_nsu' => '10', 'max_nsu' => '12']);

    $this->actingAs(User::factory()->create())->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->where('sync.up_to_date', false)
            ->where('sync.next_run_at', fn ($value) => str_contains($value, 'T12:10:00')));

    SefazSyncState::current()->update(['last_nsu' => '12']);

    $this->actingAs(User::factory()->create())->get('/')
        ->assertInertia(fn (Assert $page) => $page->where('sync.up_to_date', true));
});

test('certificate expiry is reported without exposing secrets', function () {
    SefazCertificate::factory()->create(['valid_to' => now()->addDays(42)->addHour()]);

    $this->actingAs(User::factory()->create())->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->where('certificate.days_left', 42)
            ->where('certificate.expired', false)
            ->missing('certificate.pfx')
            ->missing('certificate.password'));
});

test('certificate is null when none is registered', function () {
    $this->actingAs(User::factory()->create())->get('/')
        ->assertInertia(fn (Assert $page) => $page->where('certificate', null));
});
