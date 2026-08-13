<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_closures', function (Blueprint $table) {
            $table->id();
            $table->date('business_date')->unique();
            $table->dateTime('closed_at');
            $table->foreignId('closed_by')->constrained('users')->restrictOnDelete();
            $table->boolean('forced')->default(false);
            $table->text('force_reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('snapshot');
            $table->timestamps();

            $table->index('closed_at');
            $table->index('closed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_closures');
    }
};
