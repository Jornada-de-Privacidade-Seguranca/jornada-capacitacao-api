<?php

namespace Tests\Feature;

use App\Exceptions\JourneyAlreadyFinishedException;
use App\Repositories\Contracts\CriterionRepositoryInterface;
use App\Repositories\Contracts\JourneyProfileRepositoryInterface;
use App\Repositories\Contracts\JourneyRepositoryInterface;
use App\Repositories\Contracts\ProfessionalExperienceRepositoryInterface;
use App\Repositories\Contracts\SubcriterionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\JourneyCalculationService;
use App\Services\JourneyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\Feature\Concerns\CreatesJourneyFixtures;
use Tests\TestCase;

class JourneyMutationConcurrencyTest extends TestCase
{
    use CreatesJourneyFixtures;
    use RefreshDatabase;

    public function test_it_rejects_a_mutation_when_the_journey_finishes_after_the_initial_read(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00');

        $journeyRepository = Mockery::mock(JourneyRepositoryInterface::class);
        $journeyRepository->shouldReceive('findDetailedById')
            ->zeroOrMoreTimes()
            ->with($journey->PK_JORNADA)
            ->andReturn($journey);

        $journeyRepository->shouldReceive('findDetailedByIdForUpdate')
            ->once()
            ->with($journey->PK_JORNADA)
            ->andReturnUsing(function () use ($journey) {
                DB::table('TB_JORNADA')
                    ->where('PK_JORNADA', $journey->PK_JORNADA)
                    ->update([
                        'DH_FIM' => '2026-08-01 10:00:00',
                        'NU_PONTUACAO_TOTAL' => 71.05,
                    ]);

                return $journey->fresh();
            });

        $service = new JourneyService(
            $journeyRepository,
            Mockery::mock(CriterionRepositoryInterface::class),
            Mockery::mock(ProfessionalExperienceRepositoryInterface::class),
            Mockery::mock(SubcriterionRepositoryInterface::class),
            Mockery::mock(UserRepositoryInterface::class),
            Mockery::mock(JourneyProfileRepositoryInterface::class),
            Mockery::mock(JourneyCalculationService::class),
        );

        $this->expectException(JourneyAlreadyFinishedException::class);

        $service->updateAnswers($user, $journey->PK_JORNADA, []);
    }
}
