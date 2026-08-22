<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('subcategorias')]
#[Fillable(['categoria_id', 'nombre'])]
class Subcategoria extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function egresos(): HasMany
    {
        return $this->hasMany(Egreso::class);
    }

    #[Scope]
    protected function visiblesPara(Builder $query, int $userId): void
    {
        $query->where(fn (Builder $q) => $q->where('user_id', $userId)->orWhereNull('user_id'));
    }

    public function esDelSistema(): bool
    {
        return $this->user_id === null;
    }
}
