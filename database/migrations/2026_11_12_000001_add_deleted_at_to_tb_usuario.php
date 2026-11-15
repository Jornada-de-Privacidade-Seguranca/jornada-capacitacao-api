<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('TB_USUARIO')) {
            return;
        }

        if (! Schema::hasColumn('TB_USUARIO', 'DH_EXCLUIDO_EM')) {
            Schema::table('TB_USUARIO', function (Blueprint $table): void {
                $table->timestamp('DH_EXCLUIDO_EM')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('TB_USUARIO') || ! Schema::hasColumn('TB_USUARIO', 'DH_EXCLUIDO_EM')) {
            return;
        }

        Schema::table('TB_USUARIO', function (Blueprint $table): void {
            $table->dropColumn('DH_EXCLUIDO_EM');
        });
    }
};
