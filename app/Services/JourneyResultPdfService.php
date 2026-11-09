<?php

namespace App\Services;

use App\Exceptions\JourneyNotCompletedException;
use App\Models\Usuario;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Gera o PDF do resultado de uma jornada concluida. O acesso e as validacoes
 * sao os mesmos do detalhe da jornada.
 */
class JourneyResultPdfService
{
    public function __construct(
        private readonly JourneyService $journeyService,
        private readonly JourneyResultReportService $reportService,
    ) {}

    /**
     * @return array{content: string, filename: string}
     */
    public function generate(Usuario $authenticatedUser, int $journeyId, string $locale): array
    {
        $journey = $this->journeyService->findForAuthenticatedUser($authenticatedUser, $journeyId);

        if ($journey->DH_FIM === null) {
            throw new JourneyNotCompletedException;
        }

        $report = $this->reportService->build($journey, $locale);
        $content = Pdf::loadView('pdf.journey-result', ['report' => $report])
            ->setPaper('a4')
            ->output();

        return [
            'content' => $content,
            'filename' => sprintf('resultado-jornada-%d-%s.pdf', $journey->PK_JORNADA, $journey->DH_FIM->copy()->setTimezone(JourneyResultReportService::DISPLAY_TIMEZONE)->format('Y-m-d')),
        ];
    }
}
