<?php

namespace App\Exceptions\Booking;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class GuideNotAvailableException extends BookingException
{
    protected $message = 'The selected guide is not available on this date.';
    protected $code = Response::HTTP_CONFLICT;

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => [
                'date' => [$this->getMessage()]
            ]
        ], $this->getCode());
    }
}
