<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Align the brand colours of the existing settings row with the defaults
     * used locally (primary #a16207, accent #3a2a1a).
     */
    public function up(): void
    {
        DB::table('settings')->update([
            'color_primario' => '#a16207',
            'color_acento' => '#3a2a1a',
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->update([
            'color_primario' => '#7c2d12',
            'color_acento' => '#f59e0b',
        ]);
    }
};
