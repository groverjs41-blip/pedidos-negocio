<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('product_returnable_requirements');

        Schema::create('product_returnable_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('returnable_type_id')->constrained('returnable_types')->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['product_id', 'returnable_type_id'], 'prr_product_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_returnable_requirements');
    }
};
