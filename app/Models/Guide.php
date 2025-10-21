<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Guide extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'experience_years',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'experience_years' => 'integer',
    ];

    /**
     * Get all bookings for this guide
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(HuntingBooking::class);
    }

    /**
     * Scope to get only active guides
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by minimum experience
     */
    public function scopeMinExperience(Builder $query, int $years): Builder
    {
        return $query->where('experience_years', '>=', $years);
    }

    /**
     * Check if guide is available on specific date
     */
    public function isAvailableOn(string $date): bool
    {
        return !$this->bookings()
            ->whereDate('date', $date)
            ->exists();
    }
}
