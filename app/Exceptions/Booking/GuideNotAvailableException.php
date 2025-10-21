<?php

declare(strict_types=1);

namespace App\Exceptions\Booking;

use Symfony\Component\HttpFoundation\Response;

class GuideNotAvailableException extends BookingException
{
    protected $message = 'The selected guide is not available on this date.';
    protected $code = Response::HTTP_CONFLICT;
}
