<?php

namespace Database\Seeders;

use App\Models\Journey;
use App\Models\JourneyProfile;
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

        $journeyProfile = JourneyProfile::query()
            ->where('NO_PERFIS_PT', 'Encarregado pelo Tratamento de Dados Pessoais')
            ->first();

        if ($journeyProfile === null) {
            throw new RuntimeException('Perfil fixo de jornada nao encontrado.');
        }

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
