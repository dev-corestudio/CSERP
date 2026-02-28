<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\EventType;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_service_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained();
            $table->enum('event_type', array_column(EventType::cases(), 'value'));
            $table->timestamp('event_timestamp');
            $table->integer('elapsed_seconds')->nullable();
            $table->timestamps();

            $table->index(['production_service_id', 'event_type']);
            $table->index('event_timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_time_logs');
    }
};
