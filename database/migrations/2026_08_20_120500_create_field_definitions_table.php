<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('key'); // slug estable para guardar/leer el valor, no depende del label
            // text | textarea | select | checkbox | radio | pick_n | date | file
            $table->string('field_type')->default('text');
            $table->boolean('is_required')->default(false);
            $table->text('help_text')->nullable();
            // Para campos "Pick N" (ej. "Method of Verification (Pick 2)").
            $table->unsignedTinyInteger('pick_count')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['issue_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_definitions');
    }
};
