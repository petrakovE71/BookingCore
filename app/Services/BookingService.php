<?php

namespace App\Services;

use App\Exceptions\Booking\BookingException;
use App\Exceptions\Booking\GuideNotActiveException;
use App\Exceptions\Booking\GuideNotAvailableException;
use App\Models\Guide;
use App\Models\HuntingBooking;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class BookingService
{
    private const MYSQL_DUPLICATE_ENTRY_ERROR_CODE = 1062;

    /**
     * Create a new hunting tour booking with transaction and locking.
     *
     * @throws GuideNotActiveException
     * @throws GuideNotAvailableException
     * @throws BookingException
     */
    public function createBooking(array $data): HuntingBooking
    {
        try {
            return DB::transaction(function () use ($data) {
                $guide = $this->lockAndRetrieveGuide($data['guide_id']);

                $this->validateGuide($guide, $data['date']);

                $booking = HuntingBooking::create($data);
                $booking->load('guide');

                Log::info('Booking created successfully', [
                    'booking_id' => $booking->id,
                    'guide_id' => $guide->id,
                    'date' => $data['date'],
                    'participants' => $data['participants_count']
                ]);

                return $booking;
            });
        } catch (GuideNotActiveException|GuideNotAvailableException $e) {
            throw $e;
        } catch (QueryException $e) {
            $this->handleQueryException($e, $data);
        } catch (\Throwable $e) {
            $this->handleUnexpectedException($e, $data);
        }
    }

    private function lockAndRetrieveGuide(int $guideId): Guide
    {
        $guide = Guide::where('id', $guideId)
            ->lockForUpdate()
            ->first();

        if (!$guide) {
            throw new BookingException('Guide not found.');
        }

        return $guide;
    }

    private function validateGuide(Guide $guide, string $date): void
    {
        if (!$guide->is_active) {
            Log::warning('Attempt to book inactive guide', [
                'guide_id' => $guide->id,
                'date' => $date
            ]);
            throw new GuideNotActiveException();
        }

        if (!$guide->isAvailableOn($date)) {
            Log::info('Guide not available', [
                'guide_id' => $guide->id,
                'date' => $date
            ]);
            throw new GuideNotAvailableException();
        }
    }

    private function handleQueryException(QueryException $e, array $data): never
    {
        if (($e->errorInfo[1] ?? null) === self::MYSQL_DUPLICATE_ENTRY_ERROR_CODE) {
            Log::warning('Duplicate booking attempt (unique constraint)', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw new GuideNotAvailableException();
        }

        Log::error('Database error during booking creation', [
            'data' => $data,
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ]);

        throw new BookingException(
            'Database error occurred while creating booking.',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $e
        );
    }

    private function handleUnexpectedException(\Throwable $e, array $data): never
    {
        Log::error('Unexpected error during booking creation', [
            'data' => $data,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        throw new BookingException(
            'An unexpected error occurred.',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $e
        );
    }
}
