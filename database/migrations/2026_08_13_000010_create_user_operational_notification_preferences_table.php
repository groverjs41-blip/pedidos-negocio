<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_operational_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('event_type');
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('sound_enabled')->default(false);
            $table->boolean('browser_enabled')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'event_type'], 'user_event_pref_unique');
        });

        // Backfill existing users with default operational notification preferences
        $eventDefaults = [
            'ORDER_CREATED' => ['admin', 'cocina'],
            'READY' => ['admin', 'reparto', 'pedidos', 'caja'],
            'DELIVERED' => ['admin', 'caja'],
            'CANCELLED' => ['admin', 'cocina', 'reparto'],
        ];

        $users = User::with('roles')->get();
        foreach ($users as $user) {
            $userRoles = $user->roles->pluck('slug')->toArray();
            foreach ($eventDefaults as $eventType => $targetRoles) {
                $isDefaultTarget = !empty(array_intersect($userRoles, $targetRoles));
                $inApp = $isDefaultTarget;
                $sound = ($eventType === 'ORDER_CREATED' || $eventType === 'READY') ? $isDefaultTarget : false;
                $browser = false;

                DB::table('user_operational_notification_preferences')->insertOrIgnore([
                    'user_id' => $user->id,
                    'event_type' => $eventType,
                    'in_app_enabled' => $inApp,
                    'sound_enabled' => $sound,
                    'browser_enabled' => $browser,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_operational_notification_preferences');
    }
};
