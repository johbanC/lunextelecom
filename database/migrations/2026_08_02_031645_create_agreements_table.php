<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type')->default('coam_equipment');
            $table->string('status')->default('pending'); // pending | signed

            // Campos fijados por el admin al generar el enlace (no editables por el cliente)
            $table->string('account_id');
            $table->date('form_date');

            // Datos llenados por el cliente
            $table->string('owner_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('business_name')->nullable();
            $table->string('address')->nullable();
            $table->string('last_4_bank', 4)->nullable();
            $table->date('training_date')->nullable();
            $table->string('authorization_digits', 4)->nullable();
            $table->json('items')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('signature_path')->nullable();
            $table->timestamp('signed_at')->nullable();

            $table->timestamps();

            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};
