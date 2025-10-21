<?php

declare(strict_types=1);

namespace App\Exceptions\Booking;

use Symfony\Component\HttpFoundation\Response;

class GuideNotActiveException extends BookingException
{
    protected $message = 'The selected guide is not currently active.';
    protected $code = Response::HTTP_UNPROCESSABLE_ENTITY;
}
