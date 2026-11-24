<?php

use App\Support\JourneyCompetencyScriptLoader;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        JourneyCompetencyScriptLoader::load();
    }

    public function down(): void
    {
        // Intentionally does not delete fixed reference data.
    }
};
