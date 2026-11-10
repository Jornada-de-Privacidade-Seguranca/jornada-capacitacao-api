<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('TB_USUARIO', 'FK_TIPO_USUARIO')) {
            return;
        }

        DB::table('TB_USUARIO')
            ->where('ST_ATIVO', 2)
            ->whereNotNull('FK_TIPO_USUARIO')
            ->update(['ST_ATIVO' => 0]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('TB_USUARIO', 'FK_TIPO_USUARIO')) {
            return;
        }

        DB::table('TB_USUARIO')
            ->where('ST_ATIVO', 0)
            ->whereNotNull('FK_TIPO_USUARIO')
            ->update(['ST_ATIVO' => 2]);
    }
};
