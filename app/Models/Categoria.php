<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('categorias')]
// Ni user_id ni user_key son asignables: el primero lo pone el controlador,
// el segundo lo calcula MySQL (columna generada, solo lectura).
#[Fillable(['nombre', 'tipo'])]
class Categoria extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subcategorias(): HasMany
    {
        return $this->hasMany(Subcategoria::class);
    }

    public function ingresos(): HasMany
    {
        return $this->hasMany(Ingreso::class);
    }

    public function egresos(): HasMany
    {
        return $this->hasMany(Egreso::class);
    }

    /**
     * Las del usuario mas las del catalogo del sistema (user_id null).
     *
     * El parentesis del closure es obligatorio: sin el, el orWhereNull se
     * mezcla con los demas filtros de la consulta y devuelve tambien las
     * categorias de otros usuarios.
     */
    #[Scope]
    protected function visiblesPara(Builder $query, int $userId): void
    {
        $query->where(fn (Builder $q) => $q->where('user_id', $userId)->orWhereNull('user_id'));
    }

    #[Scope]
    protected function deTipo(Builder $query, string $tipo): void
    {
        $query->where('tipo', $tipo);
    }

    /**
     * Las del sistema no pueden editarse ni borrarse por un usuario.
     */
    public function esDelSistema(): bool
    {
        return $this->user_id === null;
    }
}
