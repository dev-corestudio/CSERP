<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('variants')->onDelete('cascade');

            $table->integer('version_number')->default(1);

            $table->decimal('total_materials_cost', 10, 2)->default(0);
            $table->decimal('total_services_cost', 10, 2)->default(0);
            $table->decimal('total_net', 10, 2)->default(0);
            $table->decimal('total_gross', 10, 2)->default(0);
            $table->decimal('margin_percent', 5, 2)->default(0);

            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->onDelete('set null');

            $table->text('notes')->nullable();

            $table->timestamps();

            // Unikalna para: variant + version
            $table->unique(['variant_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
