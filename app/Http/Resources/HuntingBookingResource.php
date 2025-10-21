<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HuntingBookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tour_name' => $this->tour_name,
            'hunter_name' => $this->hunter_name,
            'guide' => new GuideResource($this->whenLoaded('guide')),
            'date' => $this->date?->format('Y-m-d'),
            'participants_count' => $this->participants_count,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
