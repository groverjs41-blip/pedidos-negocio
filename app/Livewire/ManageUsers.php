<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;

class ManageUsers extends Component
{
    public ?string $successMessage = null;

    public function mount()
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'No tiene permiso para gestionar usuarios.');
        }
    }

    public function toggleActive(int $userId)
    {
        $user = User::findOrFail($userId);

        // Safety check: Cannot deactivate last active admin
        if ($user->active && $user->hasRole('admin')) {
            $activeAdminCount = User::where('active', true)
                ->whereHas('roles', fn ($q) => $q->where('slug', 'admin'))
                ->count();

            if ($activeAdminCount <= 1) {
                session()->flash('error', 'No se puede desactivar al único usuario Administrador activo.');
                return;
            }
        }

        $user->update(['active' => !$user->active]);
        $this->successMessage = "Estado del usuario '{$user->name}' actualizado correctamente.";
    }

    public function render()
    {
        $users = User::with('roles')
            ->orderBy('name', 'asc')
            ->get();

        return view('livewire.manage-users', [
            'users' => $users,
        ])->layout('layouts.app', ['title' => 'Gestión de Usuarios - Pedidos Negocio']);
    }
}
