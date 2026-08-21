<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('default_related_to_group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            // Umbrales del semáforo de tiempo (en días) desde la creación / último cambio de estado.
            $table->unsignedTinyInteger('sla_yellow_days')->default(2);
            $table->unsignedTinyInteger('sla_red_days')->default(3);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['ticket_type_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
