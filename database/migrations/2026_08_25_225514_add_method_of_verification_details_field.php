<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agrega un campo de texto obligatorio "Method of Verification Details"
 * justo debajo de cada campo "Method of Verification" existente en el
 * catálogo, para que el agente anote qué métodos dijo el cliente (además
 * de las opciones marcadas) — pedido explícito del equipo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $moVFields = DB::table('field_definitions')->where('label', 'Method of Verification')->get();

        foreach ($moVFields as $mov) {
            DB::table('field_definitions')
                ->where('issue_id', $mov->issue_id)
                ->where('sort_order', '>', $mov->sort_order)
                ->increment('sort_order');

            DB::table('field_definitions')->insert([
                'issue_id' => $mov->issue_id,
                'label' => 'Method of Verification Details',
                'key' => 'method_of_verification_details',
                'field_type' => 'text',
                'is_required' => true,
                'help_text' => null,
                'pick_count' => null,
                'sort_order' => $mov->sort_order + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $inserted = DB::table('field_definitions')->where('label', 'Method of Verification Details')->get();

        foreach ($inserted as $row) {
            DB::table('field_definitions')->where('id', $row->id)->delete();

            DB::table('field_definitions')
                ->where('issue_id', $row->issue_id)
                ->where('sort_order', '>', $row->sort_order)
                ->decrement('sort_order');
        }
    }
};
