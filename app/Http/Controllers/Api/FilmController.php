<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Film;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FilmController extends Controller
{
    public function index(Request $request)
    {
        $query = Film::where('source_type', '!=', 'TELEGRAM');

        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        $perPage = $request->get('per_page', 12);
        $films = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $films->items(),
            'pagination' => [
                'current_page' => $films->currentPage(),
                'per_page' => $films->perPage(),
                'total' => $films->total(),
                'last_page' => $films->lastPage(),
                'from' => $films->firstItem(),
                'to' => $films->lastItem(),
            ]
        ]);
    }

    public function show($id)
    {
        $film = Film::find($id);

        if (!$film) return $this->errorResponse('Film not found', 404);

        return $this->successResponse($film);
    }
}
