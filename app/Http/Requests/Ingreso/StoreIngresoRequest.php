<?php

namespace App\Http\Requests\Ingreso;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIngresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            // Debe existir, ser de tipo ingreso y ser visible para este usuario:
            // propia o del catalogo del sistema. Sin el filtro por usuario,
            // cualquiera podria usar una categoria privada de otra cuenta.
            //
            // El tipo 'ingreso' es lo que impide registrar un ingreso bajo
            // "Alimentacion" y descuadrar todos los reportes del dashboard.
            'categoria_id' => [
                'required',
                'integer',
                Rule::exists('categorias', 'id')
                    ->where('tipo', 'ingreso')
                    ->where(fn ($q) => $q->where('user_id', $userId)->orWhereNull('user_id')),
            ],

            'fecha' => ['required', 'date_format:Y-m-d'],

            'fuente' => ['required', 'string', 'max:150'],

            // decimal:0,2 rechaza mas de dos decimales en lugar de redondear
            // en silencio. El maximo corresponde a decimal(12,2).
            'monto' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],

            'notas' => ['nullable', 'string', 'max:65535'],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_id.exists' => 'La categoria no existe o no es de tipo ingreso.',
            'monto.decimal' => 'El monto admite como maximo dos decimales.',
            'monto.min' => 'El monto debe ser mayor que cero.',
            'fecha.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
            'fuente.required' => 'La fuente del ingreso es obligatoria.',
        ];
    }
}
