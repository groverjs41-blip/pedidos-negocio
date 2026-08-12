<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea un usuario administrador interactivo';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('--- Crear Usuario Administrador ---');

        $name = $this->ask('Nombre');
        $email = $this->ask('Correo electrónico');

        // Validate email format
        $validator = Validator::make(['email' => $email], [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            $this->error('El correo electrónico no es válido.');
            return 1;
        }

        // Prompt password and confirmation (hidden)
        $password = $this->secret('Contraseña');
        $passwordConfirm = $this->secret('Confirmar contraseña');

        if ($password !== $passwordConfirm) {
            $this->error('Las contraseñas no coinciden.');
            return 1;
        }

        // Validate password strength
        $validatorPwd = Validator::make(['password' => $password], [
            'password' => ['required', 'min:8'],
        ]);

        if ($validatorPwd->fails()) {
            $this->error('La contraseña debe tener al menos 8 caracteres.');
            return 1;
        }

        // Retrieve admin role
        $adminRole = Role::where('slug', 'admin')->first();
        if (!$adminRole) {
            $this->error('El rol de administrador no se encuentra en la base de datos. Ejecuta las migraciones primero.');
            return 1;
        }

        // Check if user exists
        $user = User::where('email', $email)->first();

        if ($user) {
            $this->info("El usuario con email {$email} ya existe.");
            
            // Promote to admin and active
            $user->active = true;
            $user->password = Hash::make($password);
            $user->save();
            $user->assignRole('admin');

            $this->info("El usuario {$user->name} ha sido activado y asignado como administrador.");
        } else {
            // Create user
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'active' => true,
            ]);

            $user->assignRole('admin');

            $this->info("Administrador {$name} creado exitosamente.");
        }

        return 0;
    }
}
