<?php

namespace App\Http\Requests\Categoria;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Solo se permite renombrar.
 *
 * El `tipo` no se puede cambiar a proposito: una categoria de egreso con
 * movimientos asociados que pasara a ser de ingreso dejaria esos egresos
 * apuntando a una categoria del tipo equivocado, y todos los reportes del
 * dashboard quedarian descuadrados sin que nada falle visiblemente.
 */
class UpdateCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        // El controlador vuelve a buscarla con scope de usuario y devuelve 404
        // si no es suya; aqui solo se necesita el tipo para el indice unico.
        $categoria = $this->user()->categorias()->find($this->route('categoria'));

        return [
            'nombre' => [
                'required',
                'string',
                'max:80',
                Rule::unique('categorias', 'nombre')
                    ->where(fn ($q) => $q
                        ->where('tipo', $categoria?->tipo)
                        ->where(fn ($q2) => $q2->where('user_id', $userId)->orWhereNull('user_id')))
                    ->ignore($categoria?->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe una categoria con ese nombre y tipo.',
        ];
    }
}
