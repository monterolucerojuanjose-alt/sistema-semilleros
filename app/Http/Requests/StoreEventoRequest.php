<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['Administrador', 'Coordinador']) ?? false;
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string|max:5000',
            'imagen' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'precio' => 'required|numeric|min:0',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'ubicacion' => 'nullable|string|max:255',
            'cupo_maximo' => 'nullable|integer|min:1',
            'estado' => 'required|in:activo,inactivo,finalizado',
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'El titulo del evento es obligatorio.',
            'descripcion.required' => 'La descripcion del evento es obligatoria.',
            'precio.required' => 'El precio del evento es obligatorio.',
            'precio.numeric' => 'El precio debe ser un numero valido.',
            'precio.min' => 'El precio no puede ser negativo.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha valida.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha valida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
            'cupo_maximo.integer' => 'El cupo maximo debe ser un numero entero.',
            'cupo_maximo.min' => 'El cupo maximo debe ser mayor a 0.',
            'estado.required' => 'El estado del evento es obligatorio.',
            'estado.in' => 'El estado debe ser: activo, inactivo o finalizado.',
            'imagen.image' => 'El archivo debe ser una imagen valida.',
            'imagen.mimes' => 'La imagen debe ser de tipo: jpeg, jpg, png o webp.',
            'imagen.max' => 'La imagen no puede pesar mas de 5MB.',
        ];
    }
}
