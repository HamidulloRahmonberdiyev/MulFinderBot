<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchFilmRequest;
use App\Http\Resources\Film\FilmMinResource;
use App\Services\Search\FilmSearchService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class Searchcontroller extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly FilmSearchService $filmSearchService
    ) {}

    public function search(SearchFilmRequest $request): JsonResponse
    {
        $films = $this->filmSearchService->searchFilms(
            $request->input('name')
        );

        return $this->successResponse(FilmMinResource::collection($films));
    }
}
