<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\VariantType;
use App\Enums\VariantStatus;

return new class extends Migration {
    public function up(): void
    {
        // Dropujemy tabelę, aby odświeżyć strukturę (środowisko dev)
        Schema::dropIfExists('variants');

        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');

            // Relacja rodzic–dziecko dla duplikowania wariantów.
            $table->unsignedBigInteger('parent_variant_id')->nullable();

            // Numeracja wariantu w ramach projektu (A, B, C... lub A1, A2...)
            $table->string('variant_number');

            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('quantity');

            // Typ: Prototyp / Seryjna
            $table->enum('type', array_column(VariantType::cases(), 'value'))
                ->default(VariantType::SERIAL->value);

            // Status: DRAFT, QUOTATION, PRODUCTION...
            $table->enum('status', array_column(VariantStatus::cases(), 'value'))
                ->default(VariantStatus::DRAFT->value);

            $table->boolean('is_approved')->default(false);
            $table->text('feedback_notes')->nullable();

            $table->unsignedBigInteger('approved_prototype_id')->nullable();

            // TKW z wyceny
            $table->decimal('tkw_z_wyceny', 10, 2)->nullable();

            $table->timestamps();

            $table->index(['project_id', 'variant_number']);

            // Klucz obcy do samej tabeli (self-referencing)
            $table->foreign('parent_variant_id')
                ->references('id')
                ->on('variants')
                ->nullOnDelete();

            $table->boolean('is_group')
                ->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variants');
    }
};
