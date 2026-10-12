<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProyectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:255',
            'resumen' => 'required|string|min:50',
            'semillero_id' => 'required|exists:semilleros,id',
        ];
    }
}
