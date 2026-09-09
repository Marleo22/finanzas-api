<?php

namespace App\Http\Requests\Categoria;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'tipo' => ['required', 'in:ingreso,egreso'],

            'nombre' => [
                'required',
                'string',
                'max:80',

                // La base ya impide duplicados con el indice unico sobre
                // (user_key, nombre, tipo). Validar aqui convierte un error
                // 500 de MySQL en un 422 con mensaje legible.
                //
                // Se comparan tambien las del sistema (user_id null): permitir
                // que el usuario cree su propia "Vivienda" pondria dos
                // entradas identicas en el selector del formulario.
                Rule::unique('categorias', 'nombre')
                    ->where(fn ($q) => $q
                        ->where('tipo', $this->input('tipo'))
                        ->where(fn ($q2) => $q2->where('user_id', $userId)->orWhereNull('user_id'))),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe una categoria con ese nombre y tipo.',
            'tipo.in' => 'El tipo debe ser ingreso o egreso.',
        ];
    }
}
