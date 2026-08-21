<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('field_definition_id')->constrained()->cascadeOnDelete();
            // Texto plano para campos de valor único; JSON-encoded para checkbox/pick_n.
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['ticket_id', 'field_definition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_field_values');
    }
};
