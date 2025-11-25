<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

class SearchFilmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
