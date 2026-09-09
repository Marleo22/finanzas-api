<?php

namespace App\Http\Requests\Ingreso;

use Illuminate\Foundation\Http\FormRequest;

class IndexIngresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'anio' => ['nullable', 'integer', 'between:2000,2100', 'required_with:mes'],
            'mes' => ['nullable', 'integer', 'between:1,12', 'required_with:anio'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'mes.between' => 'El mes debe estar entre 1 y 12.',
            'anio.required_with' => 'Para filtrar por mes hay que enviar tambien el anio.',
            'mes.required_with' => 'Para filtrar por anio hay que enviar tambien el mes.',
        ];
    }

    /**
     * Solo hay filtro de periodo si llegaron ambos parametros.
     */
    public function tienePeriodo(): bool
    {
        return $this->filled('anio') && $this->filled('mes');
    }
}
