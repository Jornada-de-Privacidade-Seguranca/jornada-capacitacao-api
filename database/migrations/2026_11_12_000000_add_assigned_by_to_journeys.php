<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('TB_JORNADA')) {
            return;
        }

        if (! Schema::hasColumn('TB_JORNADA', 'FK_USUARIO_ATRIBUIU')) {
            Schema::table('TB_JORNADA', function (Blueprint $table): void {
                $table->unsignedInteger('FK_USUARIO_ATRIBUIU')->nullable();
                $table->foreign('FK_USUARIO_ATRIBUIU', 'FK_JORNADA_USUARIO_ATRIBUIU')
                    ->references('PK_USUARIO')
                    ->on('TB_USUARIO');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('TB_JORNADA') || ! Schema::hasColumn('TB_JORNADA', 'FK_USUARIO_ATRIBUIU')) {
            return;
        }

        Schema::table('TB_JORNADA', function (Blueprint $table): void {
            $table->dropForeign(['FK_USUARIO_ATRIBUIU']);
            $table->dropColumn('FK_USUARIO_ATRIBUIU');
        });
    }
};
