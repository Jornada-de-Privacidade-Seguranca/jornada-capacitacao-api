<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        $hasFractionalScores = DB::table('TB_JORNADA')
            ->whereNotNull('NU_PONTUACAO_TOTAL')
            ->pluck('NU_PONTUACAO_TOTAL')
            ->contains(fn ($score): bool => fmod(abs((float) $score), 1.0) > PHP_FLOAT_EPSILON);

        if ($hasFractionalScores) {
            throw new RuntimeException(
                'Nao e possivel reverter NU_PONTUACAO_TOTAL para inteiro enquanto houver pontuacoes fracionarias.',
            );
        }

        Schema::table('TB_JORNADA', function (Blueprint $table): void {
            $table->integer('NU_PONTUACAO_TOTAL')->nullable()->change();
        });
    }
};
