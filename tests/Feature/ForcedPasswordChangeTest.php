<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('new employees and reset passwords require a change', function () {
    $admin = User::factory()->create();
    $employee = User::factory()->employee()->create();

    $this->actingAs($admin)->post('/funcionarios', ['name' => 'Carlos', 'email' => 'carlos@example.com']);
    expect(User::where('email', 'carlos@example.com')->firstOrFail()->must_change_password)->toBeTrue();

    $this->actingAs($admin)->post("/funcionarios/{$employee->id}/senha");
    expect($employee->fresh()->must_change_password)->toBeTrue();
});

test('a user with a temporary password is held on the password page', function (string $uri) {
    $this->actingAs(User::factory()->employee()->create(['must_change_password' => true]))
        ->get($uri)
        ->assertRedirect('/settings/password');
})->with(['/', '/tabelas-de-preco', '/settings/profile']);

test('the password page stays reachable and warns the user', function () {
    $this->actingAs(User::factory()->employee()->create(['must_change_password' => true]))
        ->get('/settings/password')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('auth.user.must_change_password', true));
});

test('admins with a temporary password are held as well', function () {
    $this->actingAs(User::factory()->create(['must_change_password' => true]))
        ->get('/nfe-importadas')
        ->assertRedirect('/settings/password');
});

test('changing the password releases the user', function () {
    $user = User::factory()->employee()->create(['must_change_password' => true]);

    $this->actingAs($user)
        ->put('/settings/password', [
            'current_password' => 'password',
            'password' => 'nova-senha-segura',
            'password_confirmation' => 'nova-senha-segura',
        ])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->must_change_password)->toBeFalse()
        ->and(Hash::check('nova-senha-segura', $user->fresh()->password))->toBeTrue();

    $this->get('/tabelas-de-preco')->assertOk();
});

test('the new password must differ from the temporary one', function () {
    $user = User::factory()->employee()->create(['must_change_password' => true]);

    $this->actingAs($user)
        ->put('/settings/password', [
            'current_password' => 'password',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertSessionHasErrors('password');

    expect($user->fresh()->must_change_password)->toBeTrue();
});

test('users with a temporary password can still log out', function () {
    $this->actingAs(User::factory()->employee()->create(['must_change_password' => true]))
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});
