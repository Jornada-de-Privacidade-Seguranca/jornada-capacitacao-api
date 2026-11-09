<?php

namespace Tests\Feature;

use App\Models\AcquiredKnowledge;
use App\Models\Competency;
use App\Models\Difficulty;
use App\Models\Journey;
use App\Models\JourneyProfile;
use App\Models\KnowledgeArea;
use App\Models\KnowledgeDomain;
use App\Models\ProfessionalExperience;
use App\Models\Usuario;
use App\Services\JourneyResultPdfService;
use App\Services\JourneyResultReportService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class JourneyResultPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_downloads_completed_journey_result_as_pdf_attachment(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->completedJourneyFor($user);

        $response = $this->actingAs($user)
            ->get("/api/journeys/{$journey->PK_JORNADA}/pdf")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader(
                'Content-Disposition',
                "attachment; filename=\"resultado-jornada-{$journey->PK_JORNADA}-2026-07-24.pdf\"",
            );

        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_it_exposes_content_disposition_header_to_the_frontend(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->completedJourneyFor($user);

        $this->actingAs($user)
            ->withHeader('Origin', 'http://localhost:3000')
            ->get("/api/journeys/{$journey->PK_JORNADA}/pdf")
            ->assertOk()
            ->assertHeader('Access-Control-Expose-Headers', 'Content-Disposition');
    }

    public function test_administrator_can_download_journey_result_from_any_user(): void
    {
        $admin = $this->activeUser('Admin Sistema', 1);
        $journey = $this->completedJourneyFor($this->activeUser('Maria Silva', 2));

        $this->actingAs($admin)
            ->get("/api/journeys/{$journey->PK_JORNADA}/pdf")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_it_accepts_english_locale(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->completedJourneyFor($user);

        $this->actingAs($user)
            ->get("/api/journeys/{$journey->PK_JORNADA}/pdf?locale=en")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_common_user_cannot_download_journey_result_from_another_user(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->completedJourneyFor($this->activeUser('Joao Souza', 2));

        $this->actingAs($user)
            ->getJson("/api/journeys/{$journey->PK_JORNADA}/pdf")
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'JOURNEY_FORBIDDEN');
    }

    public function test_it_rejects_unauthenticated_user(): void
    {
        $this->getJson('/api/journeys/1/pdf')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_it_returns_not_found_when_journey_does_not_exist(): void
    {
        $user = $this->activeUser('Maria Silva', 2);

        $this->actingAs($user)
            ->getJson('/api/journeys/999999/pdf')
            ->assertNotFound()
            ->assertJsonPath('code', 'JOURNEY_NOT_FOUND');
    }

    public function test_it_rejects_invalid_journey_identifier(): void
    {
        $user = $this->activeUser('Maria Silva', 2);

        $this->actingAs($user)
            ->getJson('/api/journeys/invalid/pdf')
            ->assertUnprocessable()
            ->assertJsonPath('code', 'INVALID_JOURNEY_IDENTIFIER')
            ->assertJsonPath('errors.journey.0', 'O identificador da jornada deve ser um numero inteiro maior ou igual a 1.');
    }

    public function test_it_rejects_unsupported_locale(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->completedJourneyFor($user);

        $this->actingAs($user)
            ->getJson("/api/journeys/{$journey->PK_JORNADA}/pdf?locale=es")
            ->assertUnprocessable()
            ->assertJsonPath('code', 'INVALID_LOCALE')
            ->assertJsonPath('errors.locale.0', 'O idioma deve ser pt ou en.');
    }

    public function test_it_rejects_journey_still_in_progress(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->completedJourneyFor($user, finishedAt: null);

        $this->actingAs($user)
            ->getJson("/api/journeys/{$journey->PK_JORNADA}/pdf")
            ->assertConflict()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'A jornada ainda nao foi finalizada.')
            ->assertJsonPath('code', 'JOURNEY_NOT_COMPLETED');
    }

    public function test_it_rejects_completed_journey_with_missing_required_data(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $journey = Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'DH_CRIADO_EM' => '2026-07-23 16:30:00',
            'DH_FIM' => '2026-07-23 17:30:00',
        ]);

        $this->actingAs($user)
            ->getJson("/api/journeys/{$journey->PK_JORNADA}/pdf")
            ->assertConflict()
            ->assertJsonPath('code', 'INCOMPLETE_JOURNEY')
            ->assertJsonPath('errors.fields', ['journey_profile']);
    }

    public function test_it_returns_standard_error_when_pdf_generation_fails(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $pdfService = Mockery::mock(JourneyResultPdfService::class);
        $pdfService->shouldReceive('generate')->once()->andThrow(new Exception('Unexpected failure.'));
        $this->app->instance(JourneyResultPdfService::class, $pdfService);

        $this->actingAs($user)
            ->getJson('/api/journeys/1/pdf')
            ->assertInternalServerError()
            ->assertJsonPath('code', 'JOURNEY_PDF_FAILED');
    }

    public function test_report_has_the_result_screen_content_in_portuguese(): void
    {
        $journey = $this->completedJourneyFor($this->activeUser('Maria Silva', 2), score: 72.5);

        $html = $this->renderReport($journey, 'pt');

        $this->assertStringContainsString('Encarregado', $html);
        $this->assertStringContainsString('Maria Silva', $html);
        $this->assertStringContainsString('24 de julho de 2026', $html);
        $this->assertStringContainsString('<span class="score">73</span>', $html);
        $this->assertStringContainsString('<td class="muted">Domínio</td><td>Privacidade</td>', $html);
        $this->assertStringContainsString('Médio', $html);
        $this->assertStringContainsString('2 de 3 competências declaradas', $html);
        $this->assertStringContainsString('Recomenda-se no mínimo 60% de conhecimento na área.', $html);
        $this->assertStringContainsString('Governança', $html);
        $this->assertStringContainsString('Competências declaradas', $html);
        $this->assertStringContainsString('Competências não declaradas', $html);
        $this->assertStringContainsString('Nível: Básico', $html);
        $this->assertStringContainsString('Domínio: Privacidade', $html);
        $this->assertStringNotContainsString('1 a 3 anos', $html);
    }

    public function test_report_has_the_result_screen_content_in_english(): void
    {
        $journey = $this->completedJourneyFor($this->activeUser('Maria Silva', 2), score: 72.5);

        $html = $this->renderReport($journey, 'en');

        $this->assertStringContainsString('DPO', $html);
        $this->assertStringContainsString('July 24, 2026', $html);
        $this->assertStringContainsString('<td class="muted">Domain</td><td>Privacy</td>', $html);
        $this->assertStringContainsString('Medium', $html);
        $this->assertStringContainsString('2 of 3 competencies declared', $html);
        $this->assertStringContainsString('At least 60% knowledge in each area is recommended.', $html);
        $this->assertStringContainsString('Governance', $html);
        $this->assertStringContainsString('Declared competencies', $html);
        $this->assertStringContainsString('Level: Basic', $html);
        $this->assertStringContainsString('Domain: Privacy', $html);
    }

    public function test_report_shows_dates_in_brasilia_time(): void
    {
        $this->travelTo(Carbon::parse('2026-08-01 01:30:00', 'UTC'));
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->completedJourneyFor($user, finishedAt: '2026-07-25 02:00:00');

        $portuguese = $this->renderReport($journey, 'pt');
        $english = $this->renderReport($journey, 'en');

        $this->assertStringContainsString('24 de julho de 2026', $portuguese);
        $this->assertStringContainsString('Gerado em 31 de julho de 2026 às 22:30 (horário de Brasília)', $portuguese);
        $this->assertStringContainsString('Generated on July 31, 2026 10:30 PM (Brasília time)', $english);
        $this->actingAs($user)
            ->get("/api/journeys/{$journey->PK_JORNADA}/pdf")
            ->assertHeader('Content-Disposition', "attachment; filename=\"resultado-jornada-{$journey->PK_JORNADA}-2026-07-24.pdf\"");
    }

    public function test_report_calculates_area_scores_and_groups_competencies(): void
    {
        $journey = $this->completedJourneyFor($this->activeUser('Maria Silva', 2), score: 72.5);

        $report = app(JourneyResultReportService::class)->build($this->detailed($journey), 'pt');

        $this->assertSame(['value' => 73, 'band' => 'medium', 'band_label' => 'Médio'], $report['score']);
        $this->assertSame(2, $report['declared_count']);
        $this->assertSame(3, $report['total_count']);
        $this->assertFalse($report['is_joint']);
        $this->assertSame('Privacidade', $report['domain_name']);
        $this->assertCount(1, $report['area_groups']);
        $this->assertNull($report['area_groups'][0]['title']);
        $areas = collect($report['area_groups'][0]['areas'])->keyBy('name');
        $this->assertSame(50, $areas['Governança']['score']['value']);
        $this->assertSame('weak', $areas['Governança']['score']['band']);
        $this->assertSame(100, $areas['Incidentes']['score']['value']);
        $this->assertSame(['Governança', 'Incidentes'], collect($report['declared_competencies'])->pluck('area')->all());
        $this->assertSame(['Governança'], collect($report['undeclared_competencies'])->pluck('area')->all());
        $this->assertSame('Transferência internacional', $report['undeclared_competencies'][0]['competencies'][0]['name']);
        $this->assertArrayNotHasKey('score_breakdown', $report);
    }

    public function test_report_splits_areas_by_domain_in_joint_journeys(): void
    {
        $journey = $this->completedJourneyFor($this->activeUser('Maria Silva', 2), score: 80);
        $security = KnowledgeDomain::create([
            'NO_DOMINIO_CONHECIMENTO_PT' => 'Segurança da Informação',
            'NO_DOMINIO_CONHECIMENTO_EN' => 'Information Security',
        ]);
        $network = KnowledgeArea::create([
            'NO_AREA_CONHECIMENTO_PT' => 'Redes',
            'NO_AREA_CONHECIMENTO_EN' => 'Networks',
        ]);
        $joint = KnowledgeDomain::create([
            'NO_DOMINIO_CONHECIMENTO_PT' => 'Conjunta (Privacidade/Segurança)',
            'NO_DOMINIO_CONHECIMENTO_EN' => 'Joint (Privacy/security)',
        ]);
        $firewall = $this->competency($security, $network, Difficulty::query()->firstOrFail(), 'Firewall', 'Firewall');
        $profile = JourneyProfile::query()->findOrFail($journey->FK_PERFIS_JORNADA);
        $profile->update(['FK_DOMINIO_CONHECIMENTO' => $joint->PK_DOMINIO_CONHECIMENTO]);
        $profile->competencies()->attach($firewall->PK_COMPETENCIAS);

        $report = app(JourneyResultReportService::class)->build($this->detailed($journey), 'en');

        $this->assertTrue($report['is_joint']);
        $this->assertSame('Joint (Privacy/security)', $report['domain_name']);
        $this->assertSame(['Privacy', 'Information Security'], collect($report['area_groups'])->pluck('title')->all());
        $this->assertSame('Networks', $report['area_groups'][1]['areas'][0]['name']);
        $this->assertSame(0, $report['area_groups'][1]['areas'][0]['score']['value']);
        $this->assertSame(['value' => 80, 'band' => 'strong', 'band_label' => 'Strong'], $report['score']);
    }

    public function test_report_keeps_the_stored_score_and_treats_missing_score_as_zero(): void
    {
        $service = app(JourneyResultReportService::class);

        $this->assertSame(75, $service->scoreValue(74.5, 'pt')['value']);
        $this->assertSame('strong', $service->scoreValue(74.5, 'pt')['band']);
        $this->assertSame(59, $service->scoreValue(59.4, 'pt')['value']);
        $this->assertSame('weak', $service->scoreValue(59.4, 'pt')['band']);
        $this->assertSame(100, $service->scoreValue(130, 'pt')['value']);
        $this->assertSame(0, $service->scoreValue(null, 'pt')['value']);
    }

    private function renderReport(Journey $journey, string $locale): string
    {
        $report = app(JourneyResultReportService::class)->build($this->detailed($journey), $locale);

        return view('pdf.journey-result', ['report' => $report])->render();
    }

    private function detailed(Journey $journey): Journey
    {
        return $journey->fresh([
            'usuario',
            'journeyProfile.knowledgeDomain',
            'journeyProfile.competencies.knowledgeDomain',
            'journeyProfile.competencies.knowledgeArea',
            'journeyProfile.competencies.difficulty',
            'acquiredKnowledges.competency.knowledgeDomain',
            'acquiredKnowledges.competency.knowledgeArea',
            'acquiredKnowledges.competency.difficulty',
        ]);
    }

    private function activeUser(string $name, int $userType): Usuario
    {
        return Usuario::create([
            'FK_TIPO_USUARIO' => $userType,
            'NO_USUARIO' => $name,
            'DS_EMAIL' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'NO_ORGAO_EMPRESA' => 'Jornada',
            'NO_SETOR' => 'Capacitacao',
            'NU_MATRICULA' => '123',
            'ST_ATIVO' => 1,
        ]);
    }

    private function competency(
        KnowledgeDomain $domain,
        KnowledgeArea $area,
        Difficulty $difficulty,
        string $namePt,
        string $nameEn,
    ): Competency {
        return Competency::create([
            'FK_DOMINIO_CONHECIMENTO' => $domain->PK_DOMINIO_CONHECIMENTO,
            'FK_AREA_CONHECIMENTO' => $area->PK_AREA_CONHECIMENTO,
            'FK_DIFICULDADE' => $difficulty->PK_DIFICULDADE,
            'NO_COMPETENCIAS_PT' => $namePt,
            'NO_COMPETENCIAS_EN' => $nameEn,
        ]);
    }

    /**
     * Jornada do perfil "Encarregado" (Privacidade) com tres competencias: duas
     * declaradas (LGPD, em Governanca, e Resposta a incidentes) e uma nao
     * declarada (Transferencia internacional, em Governanca).
     */
    private function completedJourneyFor(
        Usuario $user,
        ?string $finishedAt = '2026-07-24 10:00:00',
        ?float $score = 72.5,
    ): Journey {
        $domain = KnowledgeDomain::create([
            'NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade',
            'NO_DOMINIO_CONHECIMENTO_EN' => 'Privacy',
        ]);
        $governance = KnowledgeArea::create([
            'NO_AREA_CONHECIMENTO_PT' => 'Governança',
            'NO_AREA_CONHECIMENTO_EN' => 'Governance',
        ]);
        $incidents = KnowledgeArea::create([
            'NO_AREA_CONHECIMENTO_PT' => 'Incidentes',
            'NO_AREA_CONHECIMENTO_EN' => 'Incidents',
        ]);
        $difficulty = Difficulty::create([
            'NO_DIFICULDADE_PT' => 'Básico',
            'NO_DIFICULDADE_EN' => 'Basic',
        ]);
        $lgpd = $this->competency($domain, $governance, $difficulty, 'LGPD', 'LGPD');
        $transfer = $this->competency($domain, $governance, $difficulty, 'Transferência internacional', 'International transfer');
        $incidentResponse = $this->competency($domain, $incidents, $difficulty, 'Resposta a incidentes', 'Incident response');

        $profile = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $domain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'Encarregado',
            'NO_PERFIS_EN' => 'DPO',
        ]);
        $profile->competencies()->attach([
            $lgpd->PK_COMPETENCIAS,
            $transfer->PK_COMPETENCIAS,
            $incidentResponse->PK_COMPETENCIAS,
        ]);
        $experience = ProfessionalExperience::create([
            'DS_EXPERIENCIA_ATUACAO_PT' => '1 a 3 anos',
            'DS_EXPERIENCIA_ATUACAO_EN' => '1 to 3 years',
        ]);

        $journey = Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
            'FK_EXPERIENCIA_PROFISSIONAL' => $experience->PK_EXPERIENCIA_ATUACAO,
            'FK_ATUACAO_PERFIL' => $experience->PK_EXPERIENCIA_ATUACAO,
            'NU_PONTUACAO_TOTAL' => $score,
            'DH_CRIADO_EM' => '2026-07-23 16:30:00',
            'DH_FIM' => $finishedAt,
        ]);

        foreach ([[$lgpd, 1], [$transfer, 0], [$incidentResponse, 1]] as [$competency, $acquired]) {
            AcquiredKnowledge::create([
                'FK_JORNADA' => $journey->PK_JORNADA,
                'FK_COMPETENCIAS' => $competency->PK_COMPETENCIAS,
                'TP_ADQUIRIDO' => $acquired,
            ]);
        }

        return $journey;
    }
}
