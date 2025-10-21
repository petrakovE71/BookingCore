<?php

namespace App\Exceptions\Booking;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class GuideNotActiveException extends BookingException
{
    protected $message = 'The selected guide is not currently active.';
    protected $code = Response::HTTP_UNPROCESSABLE_ENTITY;

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => [
                'guide_id' => [$this->getMessage()]
            ]
        ], $this->getCode());
    }
}
