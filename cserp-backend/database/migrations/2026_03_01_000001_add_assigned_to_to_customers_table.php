<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Opiekun klienta (Handlowiec lub Project Manager)
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn('assigned_to');
        });
    }
};
