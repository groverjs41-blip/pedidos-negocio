<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    $record = $this->getRecord();
                    if (UserResource::isLastActiveAdmin($record)) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Error al eliminar')
                            ->body('No se puede eliminar al último administrador activo.')
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }

    /**
     * Prevent deactivating or removing admin role from the last active administrator.
     */
    protected function beforeSave(): void
    {
        $record = $this->getRecord();
        
        // If the record was an active admin
        if ($record->isActive() && $record->hasRole('admin')) {
            // Check if we are deactivating them
            $newActive = $this->data['active'] ?? true;
            
            // Check if we are removing the admin role
            $newRoles = $this->data['roles'] ?? [];
            $adminRole = \App\Models\Role::where('slug', 'admin')->first();
            $hasAdminRole = $adminRole ? in_array($adminRole->id, $newRoles) : false;

            if (!$newActive || !$hasAdminRole) {
                // Check if they are the last active admin
                $activeAdminsCount = \App\Models\User::where('active', true)
                    ->whereHas('roles', function ($query) {
                        $query->where('slug', 'admin');
                    })
                    ->count();

                if ($activeAdminsCount <= 1) {
                    \Filament\Notifications\Notification::make()
                        ->danger()
                        ->title('Error de seguridad')
                        ->body('No se puede desactivar o quitar el rol de administrador al único administrador activo.')
                        ->send();

                    $this->halt();
                }
            }
        }
    }
}
