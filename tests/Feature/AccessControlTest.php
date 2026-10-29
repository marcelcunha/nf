<?php

use App\Enums\UserRole;
use App\Models\User;

test('employees are forbidden from admin pages', function (string $method, string $uri) {
    $this->actingAs(User::factory()->employee()->create())
        ->call($method, $uri)
        ->assertForbidden();
})->with([
    ['GET', '/nfe-importadas'],
    ['GET', '/nfe-importadas/1'],
    ['GET', '/nfe-importadas/1/precificar'],
    ['PUT', '/nfe-importadas/1/precificar'],
    ['DELETE', '/nfe-importadas/1/publicar'],
    ['GET', '/certificado'],
    ['GET', '/agendamento'],
    ['PUT', '/agendamento'],
    ['GET', '/funcionarios'],
    ['GET', '/funcionarios/criar'],
    ['POST', '/funcionarios'],
]);

test('admins can open admin pages', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)->get('/nfe-importadas')->assertOk();
    $this->actingAs($admin)->get('/certificado')->assertOk();
    $this->actingAs($admin)->get('/funcionarios')->assertOk();
});

test('employees are sent to the price tables from the home page', function () {
    $this->actingAs(User::factory()->employee()->create())
        ->get('/')
        ->assertRedirect('/tabelas-de-preco');
});

test('employees and admins can open their profile', function () {
    $this->actingAs(User::factory()->employee()->create())->get('/settings/profile')->assertOk();
    $this->actingAs(User::factory()->create())->get('/settings/profile')->assertOk();
});

test('shared auth user exposes the role', function () {
    $this->actingAs(User::factory()->employee()->create())
        ->get('/tabelas-de-preco')
        ->assertInertia(fn ($page) => $page->where('auth.user.role', UserRole::Employee->value));
});

test('inactive users cannot log in', function () {
    $user = User::factory()->employee()->inactive()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Seu acesso foi desativado.']);

    $this->assertGuest();
});

test('a signed in user is logged out once the access is disabled', function () {
    $user = User::factory()->employee()->create();
    $this->actingAs($user);

    $user->is_active = false;
    $user->save();

    $this->get('/tabelas-de-preco')
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['email' => 'Seu acesso foi desativado.']);

    $this->assertGuest();
});
