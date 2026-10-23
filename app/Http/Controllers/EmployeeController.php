<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('employees/index', [
            'employees' => User::query()
                ->where('role', UserRole::Employee)
                ->orderBy('name')
                ->paginate(10, ['id', 'name', 'email', 'is_active', 'created_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('employees/create');
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $password = $this->generatePassword();

        $employee = new User([...$request->safe()->only(['name', 'email']), 'password' => $password]);
        $employee->role = UserRole::Employee;
        $employee->is_active = true;
        $employee->must_change_password = true;
        $employee->save();

        return to_route('employees.index')
            ->with('success', 'Funcionário cadastrado.')
            ->with('credentials', ['name' => $employee->name, 'email' => $employee->email, 'password' => $password]);
    }

    public function edit(User $employee): Response
    {
        $this->ensureIsEmployee($employee);

        return Inertia::render('employees/edit', [
            'employee' => $employee->only(['id', 'name', 'email', 'is_active']),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, User $employee): RedirectResponse
    {
        $this->ensureIsEmployee($employee);

        $employee->fill($request->safe()->only(['name', 'email']))->save();

        return to_route('employees.index')->with('success', 'Funcionário atualizado.');
    }

    public function resetPassword(User $employee): RedirectResponse
    {
        $this->ensureIsEmployee($employee);

        $password = $this->generatePassword();

        $employee->password = $password;
        $employee->must_change_password = true;
        $employee->save();

        return to_route('employees.index')
            ->with('success', 'Senha redefinida.')
            ->with('credentials', ['name' => $employee->name, 'email' => $employee->email, 'password' => $password]);
    }

    public function toggleActive(User $employee): RedirectResponse
    {
        $this->ensureIsEmployee($employee);

        $employee->is_active = ! $employee->is_active;
        $employee->save();

        return back()->with('success', $employee->is_active ? 'Acesso reativado.' : 'Acesso desativado.');
    }

    private function generatePassword(): string
    {
        return Str::password(12, symbols: false);
    }

    private function ensureIsEmployee(User $user): void
    {
        abort_unless($user->isEmployee(), 404);
    }
}
