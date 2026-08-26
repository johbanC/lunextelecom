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
        Schema::table('notification_rules', function (Blueprint $table) {
            $table->foreignId('related_to_group_id')->nullable()->after('category_id')
                ->constrained('groups')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_rules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('related_to_group_id');
        });
    }
};
