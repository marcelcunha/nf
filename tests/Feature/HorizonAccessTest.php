<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('horizon dashboard is limited to active admins', function () {
    expect(Gate::forUser(User::factory()->create())->allows('viewHorizon'))->toBeTrue()
        ->and(Gate::forUser(User::factory()->employee()->create())->allows('viewHorizon'))->toBeFalse()
        ->and(Gate::forUser(User::factory()->create(['is_active' => false]))->allows('viewHorizon'))->toBeFalse()
        ->and(Gate::allows('viewHorizon'))->toBeFalse();
});

test('horizon dashboard is closed in production for employees and sends guests to login', function () {
    $this->app['env'] = 'production';

    $this->get('/horizon')->assertRedirect('/login');
    $this->actingAs(User::factory()->employee()->create())->get('/horizon')->assertForbidden();
});
