<?php

namespace App\Services;

use App\Models\Journey;
use Illuminate\Support\Collection;

/**
 * Monta os dados do resultado de uma jornada concluida para o PDF, com as
 * mesmas regras da tela de resultado do front: nota geral de 0 a 100 com a
 * faixa, conhecimento por area (separado por dominio nas jornadas conjuntas) e
 * competencias declaradas e nao declaradas agrupadas por area. Nao inclui a
 * pontuacao por criterio, os dados do pre-questionario nem pesos internos.
 */
class JourneyResultReportService
{
    public const DEFAULT_LOCALE = 'pt';

    public const SUPPORTED_LOCALES = ['pt', 'en'];

    /** Minimo recomendado de conhecimento por area, em percentual. */
    public const RECOMMENDED_AREA_SCORE = 60;

    private const STRONG_MIN_SCORE = 75;

    private const MEDIUM_MIN_SCORE = 60;

    private const TRANSLATION_FILE = 'journey_result_pdf';

    /**
     * Fuso das datas exibidas no PDF. A aplicacao grava as datas em UTC, que a
     * noite ja estaria no dia seguinte para os usuarios.
     */
    public const DISPLAY_TIMEZONE = 'America/Sao_Paulo';

    /**
     * @return array<string, mixed>
     */
    public function build(Journey $journey, string $locale = self::DEFAULT_LOCALE): array
    {
        $competencies = $this->competencies($journey);
        $declaredIds = $this->declaredCompetencyIds($journey);
        $areas = $this->groupByArea($competencies, $locale);
        $isJoint = $competencies
            ->map(fn ($competency) => $competency->FK_DOMINIO_CONHECIMENTO)
            ->filter()
            ->unique()
            ->count() > 1;

        return [
            'locale' => $locale,
            'journey_id' => $journey->PK_JORNADA,
            'profile_name' => $this->localize($journey->journeyProfile?->NO_PERFIS_PT, $journey->journeyProfile?->NO_PERFIS_EN, $locale),
            'domain_name' => $this->localize(
                $journey->journeyProfile?->knowledgeDomain?->NO_DOMINIO_CONHECIMENTO_PT,
                $journey->journeyProfile?->knowledgeDomain?->NO_DOMINIO_CONHECIMENTO_EN,
                $locale,
            ),
            'user_name' => $journey->usuario?->NO_USUARIO,
            'finished_at' => $journey->DH_FIM?->copy()->setTimezone(self::DISPLAY_TIMEZONE)->locale($this->carbonLocale($locale))->isoFormat('LL'),
            'generated_at' => now(self::DISPLAY_TIMEZONE)->locale($this->carbonLocale($locale))->isoFormat('LLL'),
            'score' => $this->scoreValue($journey->NU_PONTUACAO_TOTAL, $locale),
            'declared_count' => $competencies->filter(fn ($competency) => $declaredIds->contains($competency->PK_COMPETENCIAS))->count(),
            'total_count' => $competencies->count(),
            'is_joint' => $isJoint,
            'recommended_area_score' => self::RECOMMENDED_AREA_SCORE,
            'area_groups' => $this->areaGroups($areas, $declaredIds, $isJoint, $locale),
            'declared_competencies' => $this->competencyGroups($areas, $declaredIds, true, $locale),
            'undeclared_competencies' => $this->competencyGroups($areas, $declaredIds, false, $locale),
        ];
    }

    /**
     * Nota de 0 a 100, arredondada como na tela, e a faixa correspondente.
     *
     * @return array{value: int, band: string, band_label: string}
     */
    public function scoreValue(mixed $score, string $locale): array
    {
        $value = is_numeric($score) ? (int) min(100, max(0, round((float) $score))) : 0;
        $band = match (true) {
            $value >= self::STRONG_MIN_SCORE => 'strong',
            $value >= self::MEDIUM_MIN_SCORE => 'medium',
            default => 'weak',
        };

        return [
            'value' => $value,
            'band' => $band,
            'band_label' => __(self::TRANSLATION_FILE.'.bands.'.$band, [], $locale),
        ];
    }

    /**
     * Competencias avaliadas: as do perfil e as respondidas que nao estejam mais
     * no perfil, cada uma uma unica vez.
     */
    private function competencies(Journey $journey): Collection
    {
        $competencies = collect($journey->journeyProfile?->competencies ?? [])
            ->keyBy('PK_COMPETENCIAS');

        foreach ($journey->acquiredKnowledges as $acquiredKnowledge) {
            $competency = $acquiredKnowledge->competency;
            if ($competency !== null && ! $competencies->has($competency->PK_COMPETENCIAS)) {
                $competencies->put($competency->PK_COMPETENCIAS, $competency);
            }
        }

        return $competencies->values();
    }

    private function declaredCompetencyIds(Journey $journey): Collection
    {
        return $journey->acquiredKnowledges
            ->filter(fn ($acquiredKnowledge): bool => (int) $acquiredKnowledge->TP_ADQUIRIDO === 1)
            ->pluck('FK_COMPETENCIAS')
            ->map(fn ($id): int => (int) $id)
            ->values();
    }

    /**
     * Agrupa as competencias por area, em ordem de id da area. Competencias sem
     * area ficam por ultimo. O dominio da area e o da sua primeira competencia
     * com dominio.
     *
     * @return array<int, array<string, mixed>>
     */
    private function groupByArea(Collection $competencies, string $locale): array
    {
        $areas = [];

        foreach ($competencies as $competency) {
            $area = $competency->knowledgeArea;
            $key = $area !== null ? (string) $area->PK_AREA_CONHECIMENTO : 'other';

            $areas[$key] ??= [
                'sort' => $area !== null ? (int) $area->PK_AREA_CONHECIMENTO : PHP_INT_MAX,
                'name' => $area !== null
                    ? $this->localize($area->NO_AREA_CONHECIMENTO_PT, $area->NO_AREA_CONHECIMENTO_EN, $locale)
                    : __(self::TRANSLATION_FILE.'.other_area', [], $locale),
                'domain_id' => null,
                'domain_name' => null,
                'competencies' => [],
            ];

            $domain = $competency->knowledgeDomain;
            if ($areas[$key]['domain_id'] === null && $domain !== null) {
                $areas[$key]['domain_id'] = (int) $domain->PK_DOMINIO_CONHECIMENTO;
                $areas[$key]['domain_name'] = $this->localize($domain->NO_DOMINIO_CONHECIMENTO_PT, $domain->NO_DOMINIO_CONHECIMENTO_EN, $locale);
            }

            $areas[$key]['competencies'][] = $competency;
        }

        usort($areas, fn (array $first, array $second): int => $first['sort'] <=> $second['sort']);

        return $areas;
    }

    /**
     * Percentual de competencias declaradas por area. Nas jornadas conjuntas, as
     * areas sao divididas pelo dominio, em ordem de id do dominio; nas demais,
     * formam um unico grupo sem titulo.
     *
     * @param  array<int, array<string, mixed>>  $areas
     * @return array<int, array<string, mixed>>
     */
    private function areaGroups(array $areas, Collection $declaredIds, bool $isJoint, string $locale): array
    {
        $groups = [];

        foreach ($areas as $area) {
            $key = $isJoint ? (string) ($area['domain_id'] ?? 'other') : 'all';
            $groups[$key] ??= [
                'sort' => $isJoint ? ($area['domain_id'] ?? PHP_INT_MAX) : 0,
                'title' => $isJoint
                    ? ($area['domain_name'] ?: __(self::TRANSLATION_FILE.'.other_domain', [], $locale))
                    : null,
                'areas' => [],
            ];

            $total = count($area['competencies']);
            $declared = collect($area['competencies'])
                ->filter(fn ($competency): bool => $declaredIds->contains((int) $competency->PK_COMPETENCIAS))
                ->count();
            $score = $total > 0 ? (int) round($declared / $total * 100) : 0;

            $groups[$key]['areas'][] = [
                'name' => $area['name'],
                'score' => $this->scoreValue($score, $locale),
                'declared_count' => $declared,
                'total_count' => $total,
            ];
        }

        usort($groups, fn (array $first, array $second): int => $first['sort'] <=> $second['sort']);

        return array_map(fn (array $group): array => [
            'title' => $group['title'],
            'areas' => $group['areas'],
        ], $groups);
    }

    /**
     * Competencias declaradas ou nao declaradas, agrupadas por area. Competencias
     * sem resposta contam como nao declaradas, e areas vazias nao aparecem.
     *
     * @param  array<int, array<string, mixed>>  $areas
     * @return array<int, array<string, mixed>>
     */
    private function competencyGroups(array $areas, Collection $declaredIds, bool $declared, string $locale): array
    {
        $groups = [];

        foreach ($areas as $area) {
            $competencies = collect($area['competencies'])
                ->filter(fn ($competency): bool => $declaredIds->contains((int) $competency->PK_COMPETENCIAS) === $declared)
                ->map(fn ($competency): array => [
                    'name' => $this->localize($competency->NO_COMPETENCIAS_PT, $competency->NO_COMPETENCIAS_EN, $locale),
                    'description' => $this->localize($competency->DS_COMPETENCIAS_PT, $competency->DS_COMPETENCIAS_EN, $locale),
                    'difficulty' => $competency->difficulty !== null
                        ? $this->localize($competency->difficulty->NO_DIFICULDADE_PT, $competency->difficulty->NO_DIFICULDADE_EN, $locale)
                        : null,
                    'domain' => $competency->knowledgeDomain !== null
                        ? $this->localize($competency->knowledgeDomain->NO_DOMINIO_CONHECIMENTO_PT, $competency->knowledgeDomain->NO_DOMINIO_CONHECIMENTO_EN, $locale)
                        : null,
                ])
                ->values()
                ->all();

            if ($competencies !== []) {
                $groups[] = ['area' => $area['name'], 'competencies' => $competencies];
            }
        }

        return $groups;
    }

    private function carbonLocale(string $locale): string
    {
        return $locale === 'en' ? 'en' : 'pt_BR';
    }

    /** Texto no idioma pedido, com o portugues como alternativa. */
    private function localize(?string $portuguese, ?string $english, string $locale): ?string
    {
        $text = $locale === 'en' ? ($english ?: $portuguese) : ($portuguese ?: $english);

        return $text ?: null;
    }
}
