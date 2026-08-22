<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Egreso
 */
class EgresoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'fecha'       => $this->fecha->toDateString(),
            'descripcion' => $this->descripcion,

            // Se envia como string, no como number: en JavaScript todo numero
            // es un float de 64 bits y perderia la precision exacta que
            // sostienen decimal(12,2) y el cast decimal:2.
            'monto'       => $this->monto,

            'notas'       => $this->notas,

            'categoria'   => $this->whenLoaded('categoria', fn () => [
                'id'     => $this->categoria->id,
                'nombre' => $this->categoria->nombre,
                'tipo'   => $this->categoria->tipo,
            ]),

            'subcategoria' => $this->whenLoaded('subcategoria', fn () => $this->subcategoria === null ? null : [
                'id'     => $this->subcategoria->id,
                'nombre' => $this->subcategoria->nombre,
            ]),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
