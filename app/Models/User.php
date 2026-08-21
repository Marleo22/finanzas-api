<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Al borrar un usuario, MySQL intenta cascadear a `categorias` y a los
     * movimientos a la vez. Si evalua primero el ON DELETE RESTRICT de
     * egresos.categoria_id / ingresos.categoria_id, aborta con error 1451.
     * Vaciamos los movimientos primero, dentro de la misma transaccion.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $user): void {
            DB::transaction(function () use ($user): void {
                DB::table('egresos')->where('user_id', $user->id)->delete();
                DB::table('ingresos')->where('user_id', $user->id)->delete();
            });
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
