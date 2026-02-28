<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workstation_operators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workstation_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            $table->unique(['workstation_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workstation_operators');
    }
};
