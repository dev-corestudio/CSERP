<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\TestResult;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prototypes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained()->onDelete('cascade');
            $table->integer('version_number');
            $table->boolean('is_approved')->default(false);
            $table->enum('test_result', array_column(TestResult::cases(), 'value'))
                ->default(TestResult::PENDING->value);
            $table->text('feedback_notes')->nullable();
            $table->date('sent_to_client_date')->nullable();
            $table->date('client_response_date')->nullable();
            $table->timestamps();

            $table->unique(['variant_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prototypes');
    }
};
