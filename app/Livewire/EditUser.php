<?php

namespace App\Livewire;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class EditUser extends Component
{
    public User $user;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public bool $active = true;
    public array $selectedRoles = [];

    public ?string $errorMessage = null;

    public function mount(User $user)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'No tiene permiso para editar usuarios.');
        }

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->active = (bool)$user->active;
        $this->selectedRoles = $user->roles->pluck('id')->toArray();
    }

    public function save()
    {
        $this->errorMessage = null;

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $this->user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'selectedRoles' => ['required', 'array', 'min:1'],
        ]);

        // Safety check: Cannot strip admin role if last admin
        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole && $this->user->hasRole('admin') && !in_array($adminRole->id, $this->selectedRoles)) {
            $activeAdminCount = User::where('active', true)
                ->whereHas('roles', fn ($q) => $q->where('slug', 'admin'))
                ->count();

            if ($activeAdminCount <= 1) {
                $this->errorMessage = 'No se puede remover el rol Administrador al único Administrador activo del sistema.';
                return;
            }
        }

        try {
            $data = [
                'name' => trim($this->name),
                'email' => trim(strtolower($this->email)),
                'active' => $this->active,
            ];

            if (!empty(trim($this->password))) {
                $data['password'] = Hash::make($this->password);
            }

            $this->user->update($data);
            $this->user->roles()->sync($this->selectedRoles);
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            return;
        }

        session()->flash('success', "Usuario '{$this->user->name}' actualizado correctamente.");
        $this->redirect('/gestion/usuarios');
    }

    public function render()
    {
        $roles = Role::orderBy('name', 'asc')->get();

        return view('livewire.edit-user', [
            'roles' => $roles,
        ])->layout('layouts.app', ['title' => 'Editar Usuario - Pedidos Negocio']);
    }
}
