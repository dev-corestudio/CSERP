<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\MaterialStatus;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prototype_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prototype_id')->constrained()->onDelete('cascade');
            $table->foreignId('assortment_id')->constrained('assortment')->onDelete('cascade');

            // Ilość i cena
            $table->decimal('quantity', 10, 2);
            $table->string('unit');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_cost', 10, 2);

            // Status zamówienia/dostępności
            $table->enum('status', array_column(MaterialStatus::cases(), 'value'))
                ->default(MaterialStatus::NOT_ORDERED->value);

            // Daty logistyczne
            $table->date('expected_delivery_date')->nullable();
            $table->date('ordered_at')->nullable();
            $table->date('received_at')->nullable();

            // Ilość na stanie vs zamówiona
            $table->decimal('quantity_in_stock', 10, 2)->default(0);
            $table->decimal('quantity_ordered', 10, 2)->default(0);

            // Dostawca
            $table->string('supplier')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['prototype_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prototype_materials');
    }
};
