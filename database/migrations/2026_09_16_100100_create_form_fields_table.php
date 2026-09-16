<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained('form_templates')->cascadeOnDelete();
            $table->string('label');
            $table->string('key');
            $table->string('field_type');
            $table->boolean('is_required')->default(false);
            $table->boolean('editable_by_recipient')->default(true);
            $table->string('help_text')->nullable();
            $table->unsignedTinyInteger('pick_count')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['form_template_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
