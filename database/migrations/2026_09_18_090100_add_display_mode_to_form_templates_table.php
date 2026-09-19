<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modo de presentación para el destinatario: 'fields' (lista de campos,
 * comportamiento actual) o 'narrative' (un párrafo de texto con los datos
 * ya insertados — ej. la confirmación de cambio de número para OTP — donde
 * el cliente solo lee y firma, sin cajas de campo sueltas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->string('display_mode')->default('fields')->after('mode');
        });
    }

    public function down(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->dropColumn('display_mode');
        });
    }
};
