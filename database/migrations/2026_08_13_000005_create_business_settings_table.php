<?php

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
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_name')->default('Pedidos Negocio');
            $table->string('currency_name')->default('Bolivianos');
            $table->string('currency_code')->default('BOB');
            $table->string('currency_symbol')->default('Bs');
            $table->string('currency_symbol_position')->default('BEFORE');
            $table->unsignedTinyInteger('currency_decimals')->default(2);
            $table->string('decimal_separator', 5)->default(',');
            $table->string('thousands_separator', 5)->default('.');
            $table->string('timezone')->default('America/La_Paz');
            $table->boolean('notification_sound_enabled')->default(true);
            $table->unsignedInteger('notification_volume')->default(80);
            $table->boolean('kitchen_sound_enabled')->default(true);
            $table->boolean('delivery_sound_enabled')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_settings');
    }
};
