<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HuntingBooking extends Model
{
    use HasFactory;
    protected $fillable = [
        'tour_name',
        'hunter_name',
        'guide_id',
        'date',
        'participants_count',
    ];

    protected $casts = [
        'date' => 'date',
        'participants_count' => 'integer',
    ];

    /**
     * Get the guide for this booking
     */
    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }
}
