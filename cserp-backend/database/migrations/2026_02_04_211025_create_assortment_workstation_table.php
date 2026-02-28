<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assortment_workstation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workstation_id')->constrained()->onDelete('cascade');
            $table->foreignId('assortment_id')->constrained('assortment')->onDelete('cascade');
            $table->timestamps();

            // Zapobiegamy dublowaniu par
            $table->unique(['workstation_id', 'assortment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assortment_workstation');
    }
};
