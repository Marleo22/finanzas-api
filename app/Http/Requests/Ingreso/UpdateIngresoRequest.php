<?php

namespace App\Http\Requests\Ingreso;

/**
 * Mismas reglas que al crear, pero todas opcionales para soportar PATCH.
 * Se hereda para no duplicar la logica de visibilidad de categorias.
 *
 * A diferencia del egreso, aqui no hace falta limpiar nada al cambiar de
 * categoria: el ingreso no tiene subcategoria que pueda quedar colgada.
 */
class UpdateIngresoRequest extends StoreIngresoRequest
{
    public function rules(): array
    {
        $reglas = parent::rules();

        foreach ($reglas as $campo => $validaciones) {
            $reglas[$campo] = array_values(array_filter(
                $validaciones,
                fn ($regla) => $regla !== 'required',
            ));

            array_unshift($reglas[$campo], 'sometimes');
        }

        return $reglas;
    }
}
