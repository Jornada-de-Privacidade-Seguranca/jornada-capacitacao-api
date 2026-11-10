<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('TB_JORNADA', 'TP_JORNADA_ATRIBUIDA')) {
            Schema::table('TB_JORNADA', function (Blueprint $table): void {
                $table->integer('TP_JORNADA_ATRIBUIDA')->nullable();
            });
        }
    }

    public function down(): void
    {
        // The column is part of the shared database schema and may predate this migration.
    }
};
