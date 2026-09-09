<?php

namespace App\Http\Requests\Categoria;

use Illuminate\Foundation\Http\FormRequest;

class IndexCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['nullable', 'in:ingreso,egreso'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.in' => 'El tipo debe ser ingreso o egreso.',
        ];
    }
}
