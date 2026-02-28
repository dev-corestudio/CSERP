<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\WorkstationType;
use App\Enums\WorkstationStatus;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workstations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();

            $table->enum('type', array_column(WorkstationType::cases(), 'value'));

            // Statusy techniczne maszyn można zostawić jako stringi lub zrobić dla nich osobny Enum
            // Dla uproszczenia tutaj zostawiamy hardcoded uppercase, ale można dodać WorkstationStatus Enum
            $table->enum('status', array_column(WorkstationStatus::cases(), 'value'))
                ->default(WorkstationStatus::IDLE->value);

            $table->unsignedBigInteger('current_task_id')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workstations');
    }
};
