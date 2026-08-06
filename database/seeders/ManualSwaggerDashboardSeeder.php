<?php

namespace Database\Seeders;

use App\Models\Journey;
use App\Models\JourneyProfile;
use App\Models\KnowledgeDomain;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use RuntimeException;

class ManualSwaggerDashboardSeeder extends Seeder
{
    // TO-DO: Soon as we have routes to create a journey, we should remove this seeder and create the journey through the API.
    private const USER_EMAIL = 'user@jornada.local';

    public function run(): void
    {
        $user = Usuario::query()
            ->where('DS_EMAIL', self::USER_EMAIL)
            ->first();

        if ($user === null) {
            throw new RuntimeException('Usuario user@jornada.local nao encontrado.');
        }

        $knowledgeDomain = KnowledgeDomain::query()->firstOrCreate(
            ['NO_DOMINIO_CONHECIMENTO_PT' => 'Protecao de Dados'],
            ['NO_DOMINIO_CONHECIMENTO_EN' => 'Data Protection'],
        );

        $journeyProfile = JourneyProfile::query()->firstOrCreate(
            [
                'FK_DOMINIO_CONHECIMENTO' => $knowledgeDomain->PK_DOMINIO_CONHECIMENTO,
                'DS_PERFIS_PT' => 'Painel Swagger',
            ],
            ['DS_PERFIS_EN' => 'Swagger Dashboard'],
        );

        Journey::query()->firstOrCreate(
            [
                'FK_USUARIO' => $user->PK_USUARIO,
                'FK_PERFIS_JORNADA' => $journeyProfile->PK_PERFIS_JORNADA,
                'DH_CRIADO_EM' => '2026-07-23 09:00:00',
            ],
            ['DH_FIM' => '2026-07-23 10:00:00'],
        );

        Journey::query()->firstOrCreate(
            [
                'FK_USUARIO' => $user->PK_USUARIO,
                'FK_PERFIS_JORNADA' => $journeyProfile->PK_PERFIS_JORNADA,
                'DH_CRIADO_EM' => '2026-07-24 09:00:00',
            ],
            ['DH_FIM' => null],
        );
    }
}
