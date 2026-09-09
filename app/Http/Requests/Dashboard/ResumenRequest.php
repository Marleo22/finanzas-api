<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class ResumenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'anio' => ['required', 'integer', 'between:2000,2100'],
            'mes'  => ['required', 'integer', 'between:1,12'],
        ];
    }

    public function messages(): array
    {
        return [
            'anio.required' => 'El anio es obligatorio.',
            'mes.required'  => 'El mes es obligatorio.',
            'mes.between'   => 'El mes debe estar entre 1 y 12.',
        ];
    }
}
