<?php

namespace App\Livewire;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class CreateUser extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public bool $active = true;
    public array $selectedRoles = [];

    public ?string $errorMessage = null;

    public function mount()
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'No tiene permiso para crear usuarios.');
        }

        $pedidosRole = Role::where('slug', 'pedidos')->first();
        if ($pedidosRole) {
            $this->selectedRoles[] = $pedidosRole->id;
        }
    }

    public function save()
    {
        $this->errorMessage = null;

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'selectedRoles' => ['required', 'array', 'min:1'],
        ]);

        try {
            $user = User::create([
                'name' => trim($this->name),
                'email' => trim(strtolower($this->email)),
                'password' => Hash::make($this->password),
                'active' => $this->active,
            ]);

            $user->roles()->sync($this->selectedRoles);

            app(\App\Services\OperationalNotificationPreferenceService::class)->ensureDefaultPreferences($user);
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            return;
        }

        session()->flash('success', "Usuario '{$user->name}' creado correctamente.");
        $this->redirect('/gestion/usuarios');
    }

    public function render()
    {
        $roles = Role::orderBy('name', 'asc')->get();

        return view('livewire.create-user', [
            'roles' => $roles,
        ])->layout('layouts.app', ['title' => 'Nuevo Usuario - Pedidos Negocio']);
    }
}
