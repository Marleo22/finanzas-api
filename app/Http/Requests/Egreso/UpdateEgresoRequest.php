<?php

namespace App\Http\Requests\Egreso;

/**
 * Mismas reglas que al crear, pero todas opcionales para soportar PATCH.
 * Se hereda para no duplicar la logica de visibilidad de categorias.
 */
class UpdateEgresoRequest extends StoreEgresoRequest
{
    /**
     * Si se cambia de categoria sin indicar subcategoria, la vieja quedaria
     * colgada de una categoria a la que no pertenece. Se limpia antes de
     * validar, en lugar de exigir el campo: null es un valor legitimo y un
     * 'required' lo rechazaria.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('categoria_id') && ! $this->has('subcategoria_id')) {
            $this->merge(['subcategoria_id' => null]);
        }
    }

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
