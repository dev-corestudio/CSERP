<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\AssortmentType; // IMPORT
use App\Enums\AssortmentUnit; // IMPORT

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assortment', function (Blueprint $table) {
            $table->id();

            $table->enum('type', array_column(AssortmentType::cases(), 'value'));

            $table->string('name');
            $table->string('category');

            // Jeśli chcesz wymusić tylko jednostki z Enuma w bazie:
            $table->enum('unit', array_column(AssortmentUnit::cases(), 'value'));
            // Jeśli wolisz elastyczność (string), zostaw $table->string('unit');

            $table->decimal('default_price', 10, 2);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assortment');
    }
};
