<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\DeliveryStatus;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained()->onDelete('cascade');
            $table->string('delivery_number')->unique();
            $table->date('delivery_date');
            $table->string('tracking_number')->nullable();
            $table->string('courier')->nullable();
            $table->enum('status', array_column(DeliveryStatus::cases(), 'value'))
                ->default(DeliveryStatus::SCHEDULED->value);
            $table->timestamp('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('delivery_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
