<?php

namespace App\Http\Resources\Film;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FilmMinResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'code' => $this->code,
            'details' => $this->details,
        ];
    }
}
