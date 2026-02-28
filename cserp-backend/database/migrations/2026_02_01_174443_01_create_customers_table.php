<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\CustomerType; // IMPORT

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nip', 10)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();

            // Dynamiczne wartości
            $table->enum('type', array_column(CustomerType::cases(), 'value'))
                  ->default(CustomerType::B2B->value);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('name');
            $table->index('nip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
