<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('TB_USUARIO')
            ->where('ST_ATIVO', 2)
            ->whereNotNull('FK_TIPO_USUARIO')
            ->update(['ST_ATIVO' => 0]);
    }

    public function down(): void
    {
        DB::table('TB_USUARIO')
            ->where('ST_ATIVO', 0)
            ->whereNotNull('FK_TIPO_USUARIO')
            ->update(['ST_ATIVO' => 2]);
    }
};
