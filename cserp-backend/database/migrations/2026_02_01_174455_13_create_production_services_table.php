<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ProductionStatus;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->onDelete('cascade');
            $table->integer('step_number');
            $table->string('service_name');
            $table->foreignId('workstation_id')->constrained();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Szacowane
            $table->decimal('estimated_quantity', 10, 2);
            $table->decimal('estimated_time_hours', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('estimated_cost', 10, 2);

            // Rzeczywiste
            $table->decimal('actual_quantity', 10, 2)->nullable();
            $table->decimal('actual_time_hours', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();

            // Wariancje
            $table->decimal('time_variance_hours', 10, 2)->nullable();
            $table->decimal('cost_variance', 10, 2)->nullable();
            $table->decimal('variance_percent', 8, 2)->nullable();

            // Timer
            $table->integer('total_pause_duration_seconds')->default(0);

            $table->enum('status', array_column(ProductionStatus::cases(), 'value'))
                ->default(ProductionStatus::PLANNED->value);

            // Daty
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->timestamp('actual_start_date')->nullable();
            $table->timestamp('actual_end_date')->nullable();

            $table->text('worker_notes')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('workstation_id');
            $table->index('assigned_to_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_services');
    }
};
