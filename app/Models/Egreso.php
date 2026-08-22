<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('egresos')]
// user_id NO va aqui a proposito: no debe poder llegar desde el request.
// Se asigna siempre desde el usuario autenticado en el controlador.
#[Fillable(['categoria_id', 'subcategoria_id', 'fecha', 'descripcion', 'monto', 'notas'])]
class Egreso extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function subcategoria(): BelongsTo
    {
        return $this->belongsTo(Subcategoria::class);
    }

    /**
     * Filtra por mes usando un rango semiabierto sobre `fecha`.
     *
     * No usa YEAR()/MONTH(): aplicar una funcion sobre la columna la vuelve
     * no-sargable y descarta el indice (user_id, fecha). Medido sobre 2190
     * filas, la version con funciones examina 1095 y esta 31.
     */
    #[Scope]
    protected function delMes(Builder $query, int $anio, int $mes): void
    {
        $inicio = CarbonImmutable::createFromDate($anio, $mes, 1)->startOfMonth();

        $query->where('fecha', '>=', $inicio->toDateString())
              ->where('fecha', '<', $inicio->addMonth()->toDateString());
    }
}
