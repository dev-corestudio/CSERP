<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_item_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('assortment_item_id')->constrained('assortment')->onDelete('cascade');
            $table->decimal('estimated_quantity', 10, 2);
            $table->decimal('estimated_time_hours', 10, 2);
            $table->string('unit');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_cost', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_item_services');
    }
};
