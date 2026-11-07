<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\IncompleteJourneyException;
use App\Exceptions\JourneyNotCompletedException;
use App\Http\Requests\DownloadJourneyPdfRequest;
use App\Services\JourneyResultPdfService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class JourneyResultPdfController
{
    public function __construct(private readonly JourneyResultPdfService $journeyResultPdfService) {}

    public function __invoke(DownloadJourneyPdfRequest $request): Response|JsonResponse
    {
        try {
            $pdf = $this->journeyResultPdfService->generate(
                authenticatedUser: $request->user(),
                journeyId: $request->validatedJourneyId(),
                locale: $request->validatedLocale(),
            );
        } catch (ModelNotFoundException) {
            return ApiResponse::error(
                message: 'Jornada nao encontrada.',
                status: Response::HTTP_NOT_FOUND,
                code: 'JOURNEY_NOT_FOUND',
            );
        } catch (AccessDeniedHttpException) {
            return ApiResponse::error(
                message: 'Voce nao possui permissao para visualizar esta jornada.',
                status: Response::HTTP_FORBIDDEN,
                code: 'JOURNEY_FORBIDDEN',
            );
        } catch (JourneyNotCompletedException) {
            return ApiResponse::error(
                message: 'A jornada ainda nao foi finalizada.',
                status: Response::HTTP_CONFLICT,
                code: 'JOURNEY_NOT_COMPLETED',
            );
        } catch (IncompleteJourneyException $exception) {
            return ApiResponse::error(
                message: 'Jornada concluida possui dados obrigatorios incompletos.',
                status: Response::HTTP_CONFLICT,
                code: 'INCOMPLETE_JOURNEY',
                errors: [
                    'fields' => $exception->missingFields(),
                ],
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel gerar o PDF da jornada no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'JOURNEY_PDF_FAILED',
            );
        }

        return response($pdf['content'], Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$pdf['filename'].'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
