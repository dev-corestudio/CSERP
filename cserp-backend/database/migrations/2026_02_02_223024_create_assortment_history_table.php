<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\AssortmentHistoryAction;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assortment_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assortment_id')->constrained('assortment')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('action', array_column(AssortmentHistoryAction::cases(), 'value'));
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['assortment_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assortment_history');
    }
};
