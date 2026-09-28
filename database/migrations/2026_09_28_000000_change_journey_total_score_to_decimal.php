<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('TB_JORNADA', function (Blueprint $table): void {
            $table->decimal('NU_PONTUACAO_TOTAL', 8, 3)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('TB_JORNADA', function (Blueprint $table): void {
            $table->integer('NU_PONTUACAO_TOTAL')->nullable()->change();
        });
    }
};
