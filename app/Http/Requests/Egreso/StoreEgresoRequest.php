<?php

namespace App\Http\Requests\Egreso;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEgresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            // Debe existir, ser de tipo egreso y ser visible para este usuario:
            // propia o del catalogo del sistema. Sin el filtro por usuario,
            // cualquiera podria usar una categoria privada de otra cuenta.
            'categoria_id' => [
                'required',
                'integer',
                Rule::exists('categorias', 'id')
                    ->where('tipo', 'egreso')
                    ->where(fn ($q) => $q->where('user_id', $userId)->orWhereNull('user_id')),
            ],

            // La llave foranea garantiza que la subcategoria exista, pero no
            // que pertenezca a la categoria enviada. Eso se valida aqui.
            'subcategoria_id' => [
                'nullable',
                'integer',
                Rule::exists('subcategorias', 'id')
                    ->where('categoria_id', $this->input('categoria_id'))
                    ->where(fn ($q) => $q->where('user_id', $userId)->orWhereNull('user_id')),
            ],

            'fecha' => ['required', 'date_format:Y-m-d'],

            'descripcion' => ['required', 'string', 'max:150'],

            // decimal:0,2 rechaza mas de dos decimales en lugar de redondear
            // en silencio. El maximo corresponde a decimal(12,2).
            'monto' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],

            'notas' => ['nullable', 'string', 'max:65535'],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_id.exists'    => 'La categoria no existe o no es de tipo egreso.',
            'subcategoria_id.exists' => 'La subcategoria no pertenece a la categoria seleccionada.',
            'monto.decimal'          => 'El monto admite como maximo dos decimales.',
            'monto.min'              => 'El monto debe ser mayor que cero.',
            'fecha.date_format'      => 'La fecha debe tener el formato AAAA-MM-DD.',
        ];
    }
}
