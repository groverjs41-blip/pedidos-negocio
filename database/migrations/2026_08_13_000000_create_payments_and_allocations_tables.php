<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('submission_token')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('method');
            $table->string('reference')->nullable();
            $table->dateTime('paid_at');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('paid_at');
            $table->index('method');
            $table->index('voided_at');
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->unique(['payment_id', 'order_id']);
            $table->index('payment_id');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
    }
};
