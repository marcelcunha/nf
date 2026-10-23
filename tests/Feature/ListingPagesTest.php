<?php

use App\Jobs\ImportNfesFromSefaz;
use App\Models\NfeImport;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to login', function () {
    $this->get('/nfe-importadas')->assertRedirect('/login');
    $this->get('/nfe-importadas/1')->assertRedirect('/login');
    $this->get('/nfe-importadas/1/danfe')->assertRedirect('/login');
});

test('nfe imports lists and dispatches import', function () {
    Queue::fake();
    NfeImport::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->get('/nfe-importadas')
        ->assertInertia(fn (Assert $page) => $page
            ->component('nfe-imports/index')
            ->has('imports.data', 1)
            ->where('scheduleTimes', ['06:30', '12:10', '18:40']));

    $this->actingAs($user)->post('/nfe-importadas')->assertRedirect();
    Queue::assertPushed(ImportNfesFromSefaz::class);
});

test('nfe imports can be filtered by key, number and period', function () {
    $target = NfeImport::factory()->create([
        'access_key' => str_repeat('1', 44),
        'number' => 555,
        'issued_at' => '2026-03-10 23:30:00',
    ]);
    NfeImport::factory()->create(['access_key' => str_repeat('2', 44), 'number' => 556, 'issued_at' => '2026-04-20 10:00:00']);
    $user = User::factory()->create();

    $this->actingAs($user)->get('/nfe-importadas?access_key=1111')
        ->assertInertia(fn (Assert $page) => $page
            ->has('imports.data', 1)
            ->where('imports.data.0.id', $target->id));

    $this->actingAs($user)->get('/nfe-importadas?number=555')
        ->assertInertia(fn (Assert $page) => $page->has('imports.data', 1)->where('imports.data.0.id', $target->id));

    $this->actingAs($user)->get('/nfe-importadas?from=2026-03-01&to=2026-03-10')
        ->assertInertia(fn (Assert $page) => $page
            ->has('imports.data', 1)
            ->where('imports.data.0.id', $target->id)
            ->where('filters.to', '2026-03-10'));

    $this->actingAs($user)->get('/nfe-importadas?from=2026-03-11&to=2026-03-01')
        ->assertSessionHasErrors('to');
});

test('nfe details page lists items from the xml', function () {
    $import = NfeImport::factory()->create(['xml' => file_get_contents(base_path('tests/Fixtures/nfe.xml'))]);

    $this->actingAs(User::factory()->create())
        ->get("/nfe-importadas/{$import->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('nfe-imports/show')
            ->has('details.items', 2)
            ->where('details.items.0.name', 'Parafuso')
            ->where('details.items.1.code', 'ABC2')
            ->where('details.totals.total', '21.50')
            ->where('details.items.0.taxes.icms', '0')
            ->where('details.taxes.icms', '0')
            ->where('details.taxes.approximate', null));
});

test('danfe is streamed inline as pdf', function () {
    $import = NfeImport::factory()->create(['xml' => file_get_contents(base_path('tests/Fixtures/nfe-danfe.xml'))]);

    $response = $this->actingAs(User::factory()->create())->get("/nfe-importadas/{$import->id}/danfe");

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('inline')
        ->and($response->getContent())->toStartWith('%PDF');
});

test('danfe fails gracefully when the xml is invalid', function () {
    $import = NfeImport::factory()->create(['xml' => '<NFe/>']);

    $this->actingAs(User::factory()->create())
        ->get("/nfe-importadas/{$import->id}/danfe")
        ->assertStatus(422);
});

test('nfe details reads taxes per item', function () {
    $import = NfeImport::factory()->create(['xml' => file_get_contents(base_path('tests/Fixtures/nfe-danfe.xml'))]);

    $this->actingAs(User::factory()->create())
        ->get("/nfe-importadas/{$import->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('details.items.0.taxes.icms', '2.70')
            ->where('details.items.0.taxes.ipi', '0')
            ->where('details.taxes.icms', '2.70'));
});
