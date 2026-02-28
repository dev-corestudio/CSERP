<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ProductionStatus;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');

            // Koszty
            $table->decimal('total_estimated_cost', 10, 2)->default(0);
            $table->decimal('total_actual_cost', 10, 2)->default(0);

            $table->enum('status', array_column(ProductionStatus::cases(), 'value'))
                ->default(ProductionStatus::PLANNED->value);

            // Daty
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['variant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
