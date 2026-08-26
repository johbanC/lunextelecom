<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reemplaza el ciclo de vida open/in_progress/resolved/closed por el del
 * sistema viejo que se está reemplazando: new/processing/follow_up/
 * resolved/informational (ver docs/SPEC_DESARROLLO.md). "closed" no tiene
 * equivalente directo, se remapea a "resolved" (ambos significan "ya no
 * está pendiente" para el semáforo de SLA).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('tickets')->where('status', 'open')->update(['status' => 'new']);
        DB::table('tickets')->where('status', 'in_progress')->update(['status' => 'processing']);
        DB::table('tickets')->where('status', 'closed')->update(['status' => 'resolved']);
    }

    /**
     * Reversión con pérdida de información: "closed" no se puede
     * reconstruir porque se fusionó con "resolved"; follow_up e
     * informational no existían antes, así que vuelven a "open".
     */
    public function down(): void
    {
        DB::table('tickets')->where('status', 'new')->update(['status' => 'open']);
        DB::table('tickets')->where('status', 'processing')->update(['status' => 'in_progress']);
        DB::table('tickets')->whereIn('status', ['follow_up', 'informational'])->update(['status' => 'open']);
    }
};
