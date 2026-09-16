<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Soporte para tickets en borrador: privados a su creador (y a quien tenga
 * tickets.view.all) mientras se completa la información, sin número de
 * ticket asignado hasta que se publiquen — ver app/Models/Ticket.php.
 * ticket_number se vuelve nullable con SQL crudo porque el proyecto no
 * tiene doctrine/dbal instalado (requerido por Blueprint::change()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->boolean('is_draft')->default(false)->after('status');
            $table->index(['is_draft', 'created_by']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE tickets MODIFY ticket_number VARCHAR(255) NULL');
        } elseif (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tickets ALTER COLUMN ticket_number DROP NOT NULL');
        }
        // SQLite doesn't support altering column nullability directly; ticket_number is already nullable after table recreation
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE tickets MODIFY ticket_number VARCHAR(255) NOT NULL');
        } elseif (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tickets ALTER COLUMN ticket_number SET NOT NULL');
        }
        // SQLite doesn't support altering column nullability directly

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['is_draft', 'created_by']);
            $table->dropColumn('is_draft');
        });
    }
};
