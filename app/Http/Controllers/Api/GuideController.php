<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GuideResource;
use App\Models\Guide;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GuideController extends Controller
{
    /**
     * Display a listing of active guides with optional filtering.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Guide::query()->active();

        // Filter by minimum experience if provided
        if ($request->has('min_experience')) {
            $minExperience = (int) $request->input('min_experience');
            $query->minExperience($minExperience);
        }

        $guides = $query->orderBy('experience_years', 'desc')->get();

        return GuideResource::collection($guides);
    }
}
