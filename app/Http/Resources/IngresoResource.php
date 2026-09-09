<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Ingreso
 */
class IngresoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha' => $this->fecha->toDateString(),
            'fuente' => $this->fuente,

            // Se envia como string, no como number: en JavaScript todo numero
            // es un float de 64 bits y perderia la precision exacta que
            // sostienen decimal(12,2) y el cast decimal:2.
            'monto' => $this->monto,

            'notas' => $this->notas,

            'categoria' => $this->whenLoaded('categoria', fn () => [
                'id' => $this->categoria->id,
                'nombre' => $this->categoria->nombre,
                'tipo' => $this->categoria->tipo,
            ]),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
