<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('TB_JORNADA', function (Blueprint $table): void {
            $table->json('JS_SCORE_BREAKDOWN')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('TB_JORNADA', function (Blueprint $table): void {
            $table->dropColumn('JS_SCORE_BREAKDOWN');
        });
    }
};
