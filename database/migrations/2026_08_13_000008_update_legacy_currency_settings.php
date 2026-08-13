<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('business_settings')
            ->where('currency_symbol', '$')
            ->orWhere('currency_code', 'USD')
            ->update([
                'currency_name' => 'Bolivianos',
                'currency_code' => 'BOB',
                'currency_symbol' => 'Bs',
                'currency_symbol_position' => 'BEFORE',
                'currency_decimals' => 2,
                'decimal_separator' => ',',
                'thousands_separator' => '.',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data migration down is non-destructive
    }
};
