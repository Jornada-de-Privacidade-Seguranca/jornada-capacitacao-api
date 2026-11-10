<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('TB_JORNADA', 'DH_CANCELADA_EM')) {
            Schema::table('TB_JORNADA', function (Blueprint $table): void {
                $table->timestamp('DH_CANCELADA_EM')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('TB_JORNADA', function (Blueprint $table): void {
            $table->dropColumn('DH_CANCELADA_EM');
        });
    }
};
