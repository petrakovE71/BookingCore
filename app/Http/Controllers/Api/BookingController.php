<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHuntingBookingRequest;
use App\Http\Resources\HuntingBookingResource;
use App\Models\HuntingBooking;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    /**
     * Create a new hunting tour booking.
     *
     * @param StoreHuntingBookingRequest $request
     * @return HuntingBookingResource|JsonResponse
     */
    public function store(StoreHuntingBookingRequest $request): HuntingBookingResource|JsonResponse
    {
        // All validation is handled in StoreHuntingBookingRequest
        // including guide availability and participant count

        $booking = HuntingBooking::create($request->validated());

        // Load the guide relationship for the response
        $booking->load('guide');

        return (new HuntingBookingResource($booking))
            ->response()
            ->setStatusCode(201);
    }
}
