<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('ticket_type_id')->constrained();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('issue_id')->constrained();

            // Encabezado fijo del ticket (Retailer o Customer, distinto por tipo).
            // Se guarda como JSON porque son datos capturados manualmente, no un maestro normalizado.
            $table->json('header');

            $table->string('status')->default('open'); // open | in_progress | resolved | closed
            $table->string('priority')->default('normal'); // low | normal | high | urgent

            $table->foreignId('related_to_group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');

            $table->timestamp('sla_status_since')->useCurrent(); // referencia para el cálculo del semáforo
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index('sla_status_since');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
