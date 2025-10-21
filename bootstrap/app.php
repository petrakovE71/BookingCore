<?php

use App\Exceptions\Booking\BookingException;
use App\Exceptions\Booking\GuideNotActiveException;
use App\Exceptions\Booking\GuideNotAvailableException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
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

        $exceptions->renderable(function (BookingException $e) {
            return response()->json([
                'message' => 'Unable to create booking. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    })->create();
