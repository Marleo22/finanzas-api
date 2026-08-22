<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Ingreso;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingreso>
 */
class IngresoFactory extends Factory
{
    protected $model = Ingreso::class;

    /**
     * Fuentes tipicas de un estudiante universitario en Guatemala.
     * [categoria, fuente, minimo, maximo, peso] con montos en centavos.
     *
     * El peso evita que el generador reparta parejo entre el sueldo y los
     * extras: sin el, un mes podia quedar con Q80 de intereses como unico
     * ingreso y los egresos superaban al ingreso todos los meses.
     */
    private const FUENTES = [
        ['Empleo',                 'Sueldo medio tiempo',       3200_00, 4200_00, 35],
        ['Empleo',                 'Sueldo call center',        3800_00, 4800_00, 30],
        ['Negocio Propio',         'Venta de postres',          2200_00, 3600_00, 12],
        ['Freelance / Proyecto',   'Diseño de sitio web',       2500_00, 4500_00,  9],
        ['Freelance / Proyecto',   'Mantenimiento de equipos',  1800_00, 3000_00,  6],
        ['Otro Ingreso',           'Apoyo familiar',            2000_00, 3200_00,  5],
        ['Bono / Extra',           'Bono 14',                   3500_00, 4500_00,  2],
        ['Inversión / Dividendos', 'Intereses de plazo fijo',   1500_00, 2600_00,  1],
    ];

    public function definition(): array
    {
        [$categoria, $fuente, $min, $max] = $this->fuentePonderada();

        return [
            'user_id'      => User::factory(),
            'categoria_id' => $this->categoriaId($categoria),
            'fecha'        => fake()->dateTimeThisYear(),
            'fuente'       => $fuente,
            // El monto se arma como string: dividir centavos entre 100 en PHP
            // produciria un float y perderiamos la precision exacta.
            'monto'        => $this->quetzales(fake()->numberBetween($min, $max)),
            'notas'        => null,
        ];
    }

    /**
     * Coloca el ingreso en un mes concreto, en dia de quincena o fin de mes.
     */
    public function enMes(int $anio, int $mes): static
    {
        return $this->state(function () use ($anio, $mes): array {
            $inicio = CarbonImmutable::createFromDate($anio, $mes, 1)->startOfMonth();

            return [
                'fecha' => $inicio->addDays(fake()->randomElement([0, 14, 24, $inicio->daysInMonth - 1])),
            ];
        });
    }

    public function paraUsuario(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }

    /**
     * Elige una fuente respetando el peso de cada una.
     */
    private function fuentePonderada(): array
    {
        $total = array_sum(array_column(self::FUENTES, 4));
        $tiro  = fake()->numberBetween(1, $total);

        foreach (self::FUENTES as $fuente) {
            $tiro -= $fuente[4];

            if ($tiro <= 0) {
                return array_slice($fuente, 0, 4);
            }
        }

        return array_slice(self::FUENTES[0], 0, 4);
    }

    private function categoriaId(string $nombre): int
    {
        return Categoria::whereNull('user_id')
            ->where('nombre', $nombre)
            ->where('tipo', 'ingreso')
            ->value('id');
    }

    /**
     * Centavos (int) -> string con dos decimales, sin pasar por float.
     */
    private function quetzales(int $centavos): string
    {
        return sprintf('%d.%02d', intdiv($centavos, 100), $centavos % 100);
    }
}
