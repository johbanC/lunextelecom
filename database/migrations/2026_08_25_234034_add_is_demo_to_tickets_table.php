<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca tickets generados por la herramienta de demo (Tickets -> Demo data,
 * solo disponible fuera de producción) para poder borrarlos en bloque sin
 * tocar tickets reales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('is_draft');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('is_demo');
        });
    }
};
