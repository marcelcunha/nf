<?php

use App\Enums\UserRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('admins list only employees', function () {
    User::factory()->employee()->create(['name' => 'Ana Funcionária']);
    User::factory()->create(['name' => 'Outro Admin']);

    $this->actingAs(User::factory()->create())->get('/funcionarios')
        ->assertInertia(fn (Assert $page) => $page
            ->component('employees/index')
            ->has('employees.data', 1)
            ->where('employees.data.0.name', 'Ana Funcionária'));
});

test('admins create employees with a generated password shown once', function () {
    $response = $this->actingAs(User::factory()->create())
        ->post('/funcionarios', ['name' => 'Carlos', 'email' => 'carlos@example.com'])
        ->assertRedirect('/funcionarios');

    $employee = User::where('email', 'carlos@example.com')->firstOrFail();
    $password = session('credentials.password');

    expect($employee->role)->toBe(UserRole::Employee)
        ->and($employee->is_active)->toBeTrue()
        ->and($password)->toBeString()->toHaveLength(12)
        ->and(Hash::check($password, $employee->password))->toBeTrue()
        ->and($employee->password)->not->toBe($password);

    $this->get('/funcionarios')->assertInertia(fn (Assert $page) => $page
        ->where('flash.credentials.email', 'carlos@example.com')
        ->where('flash.credentials.password', $password));

    $this->get('/funcionarios')->assertInertia(fn (Assert $page) => $page->where('flash.credentials', null));
});

test('the generated password lets the employee sign in', function () {
    $this->actingAs(User::factory()->create())
        ->post('/funcionarios', ['name' => 'Carlos', 'email' => 'carlos@example.com']);
    $password = session('credentials.password');

    auth()->logout();

    $this->post('/login', ['email' => 'carlos@example.com', 'password' => $password])->assertRedirect('/');
    $this->assertAuthenticated();
});

test('employee creation validates a unique email', function () {
    $taken = User::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post('/funcionarios', ['name' => 'Carlos', 'email' => $taken->email])
        ->assertSessionHasErrors('email');
});

test('admins update an employee without touching the password', function () {
    $employee = User::factory()->employee()->create();

    $this->actingAs(User::factory()->create())
        ->put("/funcionarios/{$employee->id}", ['name' => 'Novo Nome', 'email' => $employee->email])
        ->assertRedirect('/funcionarios');

    expect($employee->fresh()->name)->toBe('Novo Nome')
        ->and(Hash::check('password', $employee->fresh()->password))->toBeTrue();
});

test('admins generate a new password for an employee', function () {
    $employee = User::factory()->employee()->create();

    $this->actingAs(User::factory()->create())
        ->post("/funcionarios/{$employee->id}/senha")
        ->assertRedirect('/funcionarios');

    $password = session('credentials.password');

    expect(Hash::check($password, $employee->fresh()->password))->toBeTrue()
        ->and(Hash::check('password', $employee->fresh()->password))->toBeFalse();
});

test('admins disable and re-enable an employee', function () {
    $employee = User::factory()->employee()->create();
    $admin = User::factory()->create();

    $this->actingAs($admin)->patch("/funcionarios/{$employee->id}/ativo")->assertRedirect();
    expect($employee->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)->patch("/funcionarios/{$employee->id}/ativo")->assertRedirect();
    expect($employee->fresh()->is_active)->toBeTrue();
});

test('admins cannot manage other admins or themselves', function () {
    $admin = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($admin)->patch("/funcionarios/{$other->id}/ativo")->assertNotFound();
    $this->actingAs($admin)->patch("/funcionarios/{$admin->id}/ativo")->assertNotFound();
    $this->actingAs($admin)->get("/funcionarios/{$other->id}/editar")->assertNotFound();

    expect($other->fresh()->is_active)->toBeTrue()
        ->and($admin->fresh()->is_active)->toBeTrue();
});

test('employees cannot create or change users', function () {
    $employee = User::factory()->employee()->create();
    $target = User::factory()->employee()->create();

    $this->actingAs($employee)->patch("/funcionarios/{$target->id}/ativo")->assertForbidden();
    $this->actingAs($employee)->post("/funcionarios/{$target->id}/senha")->assertForbidden();
    $this->actingAs($employee)->put("/funcionarios/{$target->id}", ['name' => 'X', 'email' => 'x@example.com'])->assertForbidden();

    expect($target->fresh()->is_active)->toBeTrue();
});

test('role and active flag cannot be set through mass assignment', function () {
    $user = new User(['name' => 'X', 'email' => 'x@example.com', 'password' => 'secret', 'role' => 'admin', 'is_active' => false]);

    expect($user->role)->toBeNull()
        ->and($user->is_active)->toBeNull();
});
