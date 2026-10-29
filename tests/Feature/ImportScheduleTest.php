<?php

use App\Models\SefazSyncState;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('schedule page shows the default times until the user defines them', function () {
    $this->actingAs(User::factory()->create())->get('/agendamento')
        ->assertInertia(fn (Assert $page) => $page
            ->component('import-schedule')
            ->where('times', ['06:30', '12:10', '18:40']));
});

test('admin saves import times sorted', function () {
    $this->actingAs(User::factory()->create())
        ->put('/agendamento', ['times' => ['18:00', '07:30']])
        ->assertRedirect();

    expect(SefazSyncState::current()->scheduleTimes())->toBe(['07:30', '18:00']);
});

test('invalid import times are rejected', function (array $payload) {
    $this->actingAs(User::factory()->create())
        ->put('/agendamento', $payload)
        ->assertSessionHasErrors();

    expect(SefazSyncState::current()->schedule_times)->toBeNull();
})->with([
    'empty' => [['times' => []]],
    'bad format' => [['times' => ['25:00']]],
    'duplicated' => [['times' => ['08:00', '08:00']]],
    'more than three' => [['times' => array_map(fn ($hour) => sprintf('%02d:00', $hour), range(1, 4))]],
]);
