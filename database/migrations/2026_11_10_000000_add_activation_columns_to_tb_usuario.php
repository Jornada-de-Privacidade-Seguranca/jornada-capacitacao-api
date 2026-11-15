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

        Schema::table('TB_USUARIO', function (Blueprint $table): void {
            if (! Schema::hasColumn('TB_USUARIO', 'TP_ATIVACAO')) {
                $table->integer('TP_ATIVACAO')->nullable();
            }

            if (! Schema::hasColumn('TB_USUARIO', 'DH_ATIVACAO')) {
                $table->timestamp('DH_ATIVACAO')->nullable();
            }
        });
    }

    public function down(): void
    {
        // The official SQL schema already owns these columns. Keep them on rollback
        // so rolling back Laravel migrations cannot remove columns used by that schema.
    }
};
