<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('form_template_id')->constrained('form_templates')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending | submitted | expired
            $table->timestamp('expires_at')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('signed_ip', 45)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('reference_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('managed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('managed_at')->nullable();
            $table->timestamps();

            $table->index(['form_template_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
