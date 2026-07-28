<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('TB_USUARIO', function (Blueprint $table): void {
            $table->unique('DS_EMAIL', 'tb_usuario_ds_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('TB_USUARIO', function (Blueprint $table): void {
            $table->dropUnique('tb_usuario_ds_email_unique');
        });
    }
};
