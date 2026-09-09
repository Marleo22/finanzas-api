<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Categoria
 */
class CategoriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,

            // Le dice al frontend si mostrar los botones de editar y borrar.
            'es_del_sistema' => $this->esDelSistema(),

            'subcategorias' => $this->whenLoaded('subcategorias', fn () => $this->subcategorias
                ->map(fn ($sub) => [
                    'id' => $sub->id,
                    'nombre' => $sub->nombre,
                ])->all()),
        ];
    }
}
