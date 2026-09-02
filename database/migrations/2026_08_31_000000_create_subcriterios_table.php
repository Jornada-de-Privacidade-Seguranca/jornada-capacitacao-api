<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('TB_SUBCRITERIOS', function (Blueprint $table): void {
            $table->increments('PK_SUBCRITERIOS');
            $table->string('NO_SUBCRITERIOS_PT', 100)->nullable();
            $table->string('NO_SUBCRITERIOS_EN', 100)->nullable();
        });

        foreach ([
            1 => ['NO_SUBCRITERIOS_PT' => 'Graduação', 'NO_SUBCRITERIOS_EN' => 'Bachelor Degree'],
            2 => ['NO_SUBCRITERIOS_PT' => 'Tecnólogo', 'NO_SUBCRITERIOS_EN' => 'Technologist Degree'],
            3 => ['NO_SUBCRITERIOS_PT' => 'Licenciatura', 'NO_SUBCRITERIOS_EN' => 'Teaching Degree'],
        ] as $id => $names) {
            DB::table('TB_SUBCRITERIOS')->updateOrInsert(['PK_SUBCRITERIOS' => $id], $names);
        }

        $this->syncSequenceWithTable(
            sequence: 'TB_SUBCRITERIOS_PK_SUBCRITERIOS_seq',
            table: 'TB_SUBCRITERIOS',
            primaryKey: 'PK_SUBCRITERIOS',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('TB_SUBCRITERIOS');
    }

    private function syncSequenceWithTable(string $sequence, string $table, string $primaryKey): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(sprintf(
            'SELECT setval(\'"%s"\', (SELECT COALESCE(MAX("%s"), 1) FROM "%s"), true)',
            $sequence,
            $primaryKey,
            $table,
        ));
    }
};
