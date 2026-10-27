<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE "TB_USUARIO" ADD COLUMN "FK_PERFIL_JORNADA" INTEGER NULL REFERENCES "TB_PERFIS_JORNADA" ("PK_PERFIS_JORNADA")');

            return;
        }

        Schema::table('TB_USUARIO', function (Blueprint $table): void {
            $table->unsignedInteger('FK_PERFIL_JORNADA')->nullable();
            $table->foreign('FK_PERFIL_JORNADA')
                ->references('PK_PERFIS_JORNADA')
                ->on('TB_PERFIS_JORNADA');
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE "TB_USUARIO" DROP COLUMN "FK_PERFIL_JORNADA"');

            return;
        }

        Schema::table('TB_USUARIO', function (Blueprint $table): void {
            $table->dropForeign(['FK_PERFIL_JORNADA']);
            $table->dropColumn('FK_PERFIL_JORNADA');
        });
    }
};
