<?php

namespace App\Exceptions\Booking;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class BookingException extends Exception
{
    public function render(): JsonResponse
    {
        Log::error('Booking error occurred', [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ]);

        return response()->json([
            'message' => 'Unable to create booking. Please try again later.'
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
