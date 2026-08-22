<?php

namespace Database\Seeders;

use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Datos de prueba para verificar el dashboard.
 *
 * NO se ejecuta desde DatabaseSeeder a proposito: son datos de demostracion
 * y no deben terminar en produccion por descuido. Se corre a mano con:
 *
 *     php artisan db:seed --class=DatosDePruebaSeeder
 *
 * Requiere que CatalogoCategoriasSeeder ya haya cargado el catalogo.
 */
class DatosDePruebaSeeder extends Seeder
{
    private const PASSWORD = 'password123';

    private const USUARIOS = [
        ['name' => 'Ana Estudiante',  'email' => 'ana@finanzas.test'],
        ['name' => 'Beto Estudiante', 'email' => 'beto@finanzas.test'],
    ];

    /** Junio (6) queda fuera a proposito: caso de prueba "mes vacio". */
    private const MESES = [1, 2, 3, 4, 5, 7];

    private const ANIO = 2026;

    public function run(): void
    {
        if (DB::table('categorias')->whereNull('user_id')->count() === 0) {
            $this->command?->error('Falta el catalogo. Corre primero: php artisan db:seed');

            return;
        }

        // Semilla fija: los montos generados son los mismos en cada corrida,
        // asi los totales del dashboard se pueden verificar contra un valor
        // esperado en lugar de contra un numero que cambia cada vez.
        fake()->seed(20260821);

        foreach (self::USUARIOS as $datos) {
            $user = $this->usuario($datos);
            $this->regenerarMovimientos($user);
        }

        $this->reportar();
    }

    /**
     * firstOrCreate sobre el email, que es la llave unica real: correrlo dos
     * veces reutiliza el usuario en lugar de intentar crear otro.
     */
    private function usuario(array $datos): User
    {
        return User::firstOrCreate(
            ['email' => $datos['email']],
            ['name' => $datos['name'], 'password' => self::PASSWORD],
        );
    }

    /**
     * Borra y vuelve a generar los movimientos SOLO de este usuario de prueba.
     *
     * Sin esto el seeder duplicaria los movimientos en cada corrida y los
     * totales del dashboard dejarian de ser verificables. El borrado esta
     * acotado por user_id, asi que no toca datos de nadie mas.
     */
    private function regenerarMovimientos(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->egresos()->delete();
            $user->ingresos()->delete();

            foreach (self::MESES as $mes) {
                Ingreso::factory()
                    ->paraUsuario($user)
                    ->enMes(self::ANIO, $mes)
                    ->create();

                Egreso::factory()
                    ->count(fake()->numberBetween(6, 12))
                    ->paraUsuario($user)
                    ->enMes(self::ANIO, $mes)
                    ->create();
            }
        });
    }

    private function reportar(): void
    {
        $this->command?->info('Usuarios de prueba (password: '.self::PASSWORD.')');

        foreach (self::USUARIOS as $datos) {
            $user = User::where('email', $datos['email'])->first();

            $this->command?->info(sprintf(
                '  %-18s ingresos: %2d (Q%s)  egresos: %3d (Q%s)',
                $datos['email'],
                $user->ingresos()->count(),
                number_format((float) $user->ingresos()->sum('monto'), 2),
                $user->egresos()->count(),
                number_format((float) $user->egresos()->sum('monto'), 2),
            ));
        }

        $this->command?->info('Junio 2026 queda vacio a proposito.');
    }
}
