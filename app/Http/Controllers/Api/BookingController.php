<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHuntingBookingRequest;
use App\Http\Resources\HuntingBookingResource;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService
    ) {}

    /**
     * Create a new hunting tour booking.
     */
    public function store(StoreHuntingBookingRequest $request): JsonResponse
    {
        $booking = $this->bookingService->createBooking($request->validated());

        return (new HuntingBookingResource($booking))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
