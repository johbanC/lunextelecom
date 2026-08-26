<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rastrea qué documentos firmados ya fueron enlazados a un ticket y
 * reenviados al área encargada, para que un cambio de turno pueda ver de
 * un vistazo qué falta por gestionar (Forms -> "Gestionar").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->string('linked_ticket_number')->nullable()->after('signed_at');
            $table->foreignId('managed_by')->nullable()->after('linked_ticket_number')->constrained('users')->nullOnDelete();
            $table->timestamp('managed_at')->nullable()->after('managed_by');
        });
    }

    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('managed_by');
            $table->dropColumn(['linked_ticket_number', 'managed_at']);
        });
    }
};
