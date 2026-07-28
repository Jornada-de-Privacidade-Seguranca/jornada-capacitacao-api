<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    //Temporary migration to create profile types ADMIN and USUARIO if they do not exist
    //Exclude this after we have a proper migration for profile types
    public function up(): void
    {
        foreach (['ADMIN', 'USUARIO'] as $profileType) {
            $exists = DB::table('TB_TIPO_PERFIL')
                ->where('NO_TIPO_PERFIL', $profileType)
                ->exists();

            if (! $exists) {
                DB::table('TB_TIPO_PERFIL')->insert([
                    'NO_TIPO_PERFIL' => $profileType,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('TB_TIPO_PERFIL')
            ->whereIn('NO_TIPO_PERFIL', ['ADMIN', 'USUARIO'])
            ->delete();
    }
};
