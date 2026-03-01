<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ProjectOverallStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProjectPriority;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            // 4-cyfrowy numer projektu
            $table->string('project_number', 4);

            // Seria (4 cyfry)
            $table->string('series', 4);

            $table->text('description');

            // Planowana data realizacji
            $table->date('planned_delivery_date')->nullable();

            $table->enum('overall_status', array_column(ProjectOverallStatus::cases(), 'value'))
                ->default(ProjectOverallStatus::DRAFT->value);

            $table->enum('payment_status', array_column(PaymentStatus::cases(), 'value'))
                ->default(PaymentStatus::UNPAID->value);

            $table->enum('priority', array_column(ProjectPriority::cases(), 'value'))
                ->default(ProjectPriority::NORMAL->value);

            $table->timestamps();

            $table->index(['customer_id', 'overall_status']);

            // Unikalność pary numer + seria
            $table->unique(['project_number', 'series']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
