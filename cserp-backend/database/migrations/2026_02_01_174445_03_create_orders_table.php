<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\OrderOverallStatus;
use App\Enums\PaymentStatus;
use App\Enums\OrderPriority;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            // ZMIANA: order_number nie jest już unikalny sam w sobie (unikalna jest para order_number + series)
// Zakładamy 4 cyfry
            $table->string('order_number', 4);

            // NOWE: Seria (4 cyfry)
            $table->string('series', 4);

            // ZMIANA: Brief -> Opis
            $table->text('description');

            // NOWE: Planowana data realizacji
            $table->date('planned_delivery_date')->nullable();

            $table->enum('overall_status', array_column(OrderOverallStatus::cases(), 'value'))
                ->default(OrderOverallStatus::DRAFT->value);

            $table->enum('payment_status', array_column(PaymentStatus::cases(), 'value'))
                ->default(PaymentStatus::UNPAID->value);

            $table->enum('priority', array_column(OrderPriority::cases(), 'value'))
                ->default(OrderPriority::NORMAL->value);

            $table->timestamps();

            $table->index(['customer_id', 'overall_status']);

            // Unikalność pary numer + seria
            $table->unique(['order_number', 'series']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
