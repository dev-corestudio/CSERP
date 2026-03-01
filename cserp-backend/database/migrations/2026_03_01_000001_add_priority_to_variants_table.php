<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ProjectPriority;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->enum('priority', array_column(ProjectPriority::cases(), 'value'))
                ->default(ProjectPriority::NORMAL->value)
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
};
