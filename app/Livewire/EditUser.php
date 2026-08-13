<?php

namespace App\Livewire;

use App\Models\Role;
use App\Models\User;
use App\Services\OperationalNotificationPreferenceService;
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

    public array $notifPreferences = [
        'ORDER_CREATED' => ['in_app' => true, 'sound' => true, 'browser' => false],
        'READY' => ['in_app' => true, 'sound' => true, 'browser' => false],
        'DELIVERED' => ['in_app' => true, 'sound' => false, 'browser' => false],
        'CANCELLED' => ['in_app' => true, 'sound' => false, 'browser' => false],
    ];

    public ?string $errorMessage = null;

    public function mount(User $user)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'No tiene permiso para editar usuarios.');
        }

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->active = (bool) $user->active;
        $this->selectedRoles = $user->roles->pluck('id')->toArray();

        // Load operational notification preferences
        $prefService = app(OperationalNotificationPreferenceService::class);
        $prefService->ensureDefaultPreferences($user);

        $prefs = $user->operationalNotificationPreferences;
        foreach ($prefs as $p) {
            if (isset($this->notifPreferences[$p->event_type])) {
                $this->notifPreferences[$p->event_type] = [
                    'in_app' => (bool) $p->in_app_enabled,
                    'sound' => (bool) $p->sound_enabled,
                    'browser' => (bool) $p->browser_enabled,
                ];
            }
        }
    }

    public function enableAllNotifications(): void
    {
        foreach (array_keys($this->notifPreferences) as $event) {
            $this->notifPreferences[$event]['in_app'] = true;
            $this->notifPreferences[$event]['sound'] = in_array($event, ['ORDER_CREATED', 'READY']);
            $this->notifPreferences[$event]['browser'] = false;
        }
        $this->dispatch('notify-toast', type: 'info', title: 'Preferencias', message: 'Se activaron las notificaciones recomendadas.');
    }

    public function muteAllSounds(): void
    {
        foreach (array_keys($this->notifPreferences) as $event) {
            $this->notifPreferences[$event]['sound'] = false;
        }
        $this->dispatch('notify-toast', type: 'info', title: 'Preferencias', message: 'Se silenciaron los sonidos para todos los eventos.');
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

            // Save operational notification preferences per event
            foreach ($this->notifPreferences as $eventType => $vals) {
                $inApp = (bool) ($vals['in_app'] ?? false);
                $sound = $inApp ? (bool) ($vals['sound'] ?? false) : false;
                $browser = $inApp ? (bool) ($vals['browser'] ?? false) : false;

                $this->user->operationalNotificationPreferences()->updateOrCreate(
                    ['event_type' => $eventType],
                    [
                        'in_app_enabled' => $inApp,
                        'sound_enabled' => $sound,
                        'browser_enabled' => $browser,
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            return;
        }

        session()->flash('success', "Usuario '{$this->user->name}' actualizado correctamente.");
        $this->dispatch('notify-toast', type: 'success', title: 'Actualizado', message: 'Preferencias de notificación actualizadas.');
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
