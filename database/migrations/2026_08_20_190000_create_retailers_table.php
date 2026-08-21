<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo liviano de códigos de Retailer usados en tickets. Se auto-puebla
     * al crear un ticket con un código nuevo (no se duplican datos de contacto
     * del retailer aquí — esos viven en la otra plataforma; este código es la
     * llave para buscarlo allá).
     */
    public function up(): void
    {
        Schema::create('retailers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retailers');
    }
};
