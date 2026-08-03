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
        Schema::table('agreements', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('type')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable()->after('form_date');
            $table->string('signed_ip', 45)->nullable()->after('signature_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['expires_at', 'signed_ip']);
        });
    }
};
