<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetTrainingDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'training:reset
                            {--with-customers : Eliminar también los clientes}
                            {--force : Forzar la ejecución sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina de forma segura los datos operativos de capacitación (pedidos, pagos, cierres, notificaciones).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isProduction = app()->environment('production') || config('app.env') === 'production';
        $force = $this->option('force');
        $withCustomers = $this->option('with-customers');

        if ($isProduction && !$force) {
            $this->error('El comando debe ejecutarse con --force en entorno de producción.');
            return 1;
        }

        $this->warn('--- REINICIO DE DATOS DE CAPACITACIÓN ---');
        $this->line('Esta operación eliminará los siguientes datos operativos:');
        $this->line('- Visitas de cobranza (collection_visits)');
        $this->line('- Asignaciones de pago (payment_allocations)');
        $this->line('- Pagos (payments)');
        $this->line('- Movimientos de envases (returnable_movements)');
        $this->line('- Planes de envases de pedidos (order_returnable_plans)');
        $this->line('- Historial de estados de pedidos (order_status_histories)');
        $this->line('- Ítems de pedidos (order_items)');
        $this->line('- Pedidos (orders)');
        $this->line('- Cierres diarios (daily_closures)');
        $this->line('- Contadores diarios de pedidos (order_daily_counters)');
        $this->line('- Notificaciones (notifications)');

        if ($withCustomers) {
            $this->line('- Clientes (customers)');
        } else {
            $this->info('Nota: Los clientes (customers) SE CONSERVARÁN. Usa --with-customers si deseas eliminarlos.');
        }

        if (!$force) {
            if (!$this->confirm('Esta acción eliminará los datos de capacitación. ¿Deseas continuar?')) {
                $this->info('Operación cancelada.');
                return 0;
            }
        }

        $deletedCounts = [];

        DB::transaction(function () use (&$deletedCounts, $withCustomers) {
            $tables = [
                'collection_visits',
                'payment_allocations',
                'payments',
                'returnable_movements',
                'order_returnable_plans',
                'order_status_histories',
                'order_items',
                'orders',
                'daily_closures',
                'order_daily_counters',
                'notifications',
            ];

            if ($withCustomers) {
                $tables[] = 'customers';
            }

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $deletedCounts[$table] = DB::table($table)->delete();
                } else {
                    $deletedCounts[$table] = 0;
                }
            }
        });

        Artisan::call('cache:clear');

        $this->newLine();
        $this->info('Resumen de registros eliminados:');

        $rows = [];
        foreach ($deletedCounts as $table => $count) {
            $rows[] = [$table, $count];
        }

        $this->table(['Tabla', 'Registros Eliminados'], $rows);
        $this->info('Datos de capacitación reiniciados con éxito.');

        return 0;
    }
}
