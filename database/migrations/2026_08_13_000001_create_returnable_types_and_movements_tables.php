<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('returnable_movements');
        Schema::dropIfExists('returnable_types');

        Schema::create('returnable_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('returnable_movements', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_token');
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->restrictOnDelete();
            $table->foreignId('returnable_type_id')->constrained('returnable_types')->restrictOnDelete();
            $table->string('movement_type');
            $table->unsignedInteger('quantity');
            $table->dateTime('occurred_at');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->unique(['batch_token', 'returnable_type_id', 'movement_type'], 'rm_token_type_movement_unique');
            $table->index('customer_id');
            $table->index('order_id');
            $table->index('returnable_type_id');
            $table->index('movement_type');
            $table->index('occurred_at');
            $table->index('voided_at');
            $table->index('batch_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('returnable_movements');
        Schema::dropIfExists('returnable_types');
    }
};
