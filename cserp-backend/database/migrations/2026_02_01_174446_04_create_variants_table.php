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
            $table->foreignId('order_id')->constrained()->onDelete('cascade');

            // Relacja rodzic–dziecko dla duplikowania wariantów.
            // Dziecko (A1, A2) ma parent_variant_id = id rodzica (A).
            // Brat (B, C) ma parent_variant_id = null.
            $table->unsignedBigInteger('parent_variant_id')->nullable();

            // Numeracja wariantu w ramach zamówienia (A, B, C... lub A1, A2...)
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

            // Pola specyficzne dla zatwierdzania (np. prototypu)
            $table->boolean('is_approved')->default(false);
            $table->text('feedback_notes')->nullable();

            // Link do starego systemu prototypów (opcjonalny, zostawiamy dla kompatybilności wstecznej jeśli potrzebne)
            $table->unsignedBigInteger('approved_prototype_id')->nullable();

            // TKW z wyceny — Techniczny Koszt Wytworzenia jednej sztuki (szacunkowy)
            // Automatycznie ustawiane przy zatwierdzeniu wyceny: (materiały + usługi) / ilość,
            // ale użytkownik może je nadpisać ręcznie.
            // TKW rzeczywiste NIE jest przechowywane — obliczane na bieżąco z kosztów rzeczywistych.
            $table->decimal('tkw_z_wyceny', 10, 2)->nullable();

            $table->timestamps();

            $table->index(['order_id', 'variant_number']);

            // Klucz obcy do samej tabeli (self-referencing)
            // Definiujemy po timestamps, bo tabela musi istnieć przed FK
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
