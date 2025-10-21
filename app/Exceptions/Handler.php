<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\Booking\BookingException;
use App\Exceptions\Booking\GuideNotActiveException;
use App\Exceptions\Booking\GuideNotAvailableException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class Handler
{
    /**
     * Register exception handlers for the application.
     */
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->renderable(function (GuideNotAvailableException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => [
                    'date' => [$e->getMessage()]
                ],
            ], $e->getCode() ?: Response::HTTP_CONFLICT);
        });

        $exceptions->renderable(function (GuideNotActiveException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => [
                    'guide_id' => [$e->getMessage()]
                ],
            ], $e->getCode() ?: Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        $exceptions->reportable(function (BookingException $e): void {
            logger()->error('Booking creation failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
        });

        $exceptions->renderable(function (BookingException $e): JsonResponse {
            return response()->json([
                'message' => 'Unable to create booking. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    }
}
