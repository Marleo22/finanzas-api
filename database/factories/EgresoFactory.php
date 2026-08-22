<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Egreso;
use App\Models\Subcategoria;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Egreso>
 */
class EgresoFactory extends Factory
{
    protected $model = Egreso::class;

    /**
     * Gastos tipicos de un estudiante universitario en Guatemala.
     * [categoria, subcategoria|null, descripcion, minimo, maximo] en centavos.
     */
    private const GASTOS = [
        ['Vivienda',               'Alquiler',         'Alquiler de cuarto',        1200_00, 1800_00],
        ['Vivienda',               'Agua',             'Recibo de agua',              45_00,  110_00],
        ['Vivienda',               'Luz',              'Recibo de energia',          140_00,  320_00],
        ['Vivienda',               'Internet',         'Internet residencial',       200_00,  350_00],
        ['Educación',              'Universidad',      'Cuota mensual',              750_00, 1500_00],
        ['Educación',              'Cursos',           'Curso en linea',             120_00,  400_00],
        ['Educación',              'Libros',           'Libro de texto',             150_00,  450_00],
        ['Alimentación',           'Supermercado',     'Compra de despensa',         280_00,  800_00],
        ['Alimentación',           'Almuerzo',         'Almuerzo en la U',            30_00,   65_00],
        ['Alimentación',           'Restaurante',      'Cena fuera',                  85_00,  220_00],
        ['Transporte',             'Bus',              'Pasajes de bus',              20_00,   60_00],
        ['Transporte',             'Gasolina',         'Tanque de gasolina',         180_00,  420_00],
        ['Transporte',             'Taxi / Uber',      'Viaje nocturno',              35_00,   95_00],
        ['Transporte',             'Parqueo',          'Parqueo del campus',          15_00,   50_00],
        ['Salud',                  'Consulta',         'Consulta medica',            150_00,  350_00],
        ['Salud',                  'Medicamentos',     'Medicamentos de farmacia',    45_00,  220_00],
        ['Salud',                  'Laboratorio',      'Examenes de laboratorio',    120_00,  400_00],
        ['Ocio / Entretenimiento', 'Suscripciones',    'Streaming mensual',           45_00,   95_00],
        ['Ocio / Entretenimiento', 'Cine',             'Entrada al cine',             40_00,   80_00],
        ['Ocio / Entretenimiento', 'Salidas',          'Salida con amigos',           90_00,  280_00],
        ['Deporte',                'Gimnasio',         'Mensualidad del gimnasio',   150_00,  260_00],
        ['Deporte',                'Equipo deportivo', 'Zapatos deportivos',         250_00,  600_00],
        ['Imprevistos',            'Emergencias',      'Gasto imprevisto',           100_00,  700_00],
        ['Imprevistos',            'Reparaciones',     'Reparacion de laptop',       200_00,  900_00],
        // Categoria sin subcategorias: subcategoria_id queda null.
        ['Otro Egreso',            null,               'Gasto varios',                50_00,  300_00],
    ];

    public function definition(): array
    {
        [$categoria, $subcategoria, $descripcion, $min, $max] = fake()->randomElement(self::GASTOS);

        $categoriaId = $this->categoriaId($categoria);

        return [
            'user_id'         => User::factory(),
            'categoria_id'    => $categoriaId,
            'subcategoria_id' => $subcategoria === null
                ? null
                : $this->subcategoriaId($categoriaId, $subcategoria),
            'fecha'           => fake()->dateTimeThisYear(),
            'descripcion'     => $descripcion,
            'monto'           => $this->quetzales(fake()->numberBetween($min, $max)),
            'notas'           => null,
        ];
    }

    /**
     * Coloca el egreso en un dia cualquiera del mes indicado.
     */
    public function enMes(int $anio, int $mes): static
    {
        return $this->state(function () use ($anio, $mes): array {
            $inicio = CarbonImmutable::createFromDate($anio, $mes, 1)->startOfMonth();

            return [
                'fecha' => $inicio->addDays(fake()->numberBetween(0, $inicio->daysInMonth - 1)),
            ];
        });
    }

    public function paraUsuario(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }

    private function categoriaId(string $nombre): int
    {
        return Categoria::whereNull('user_id')
            ->where('nombre', $nombre)
            ->where('tipo', 'egreso')
            ->value('id');
    }

    private function subcategoriaId(int $categoriaId, string $nombre): int
    {
        return Subcategoria::where('categoria_id', $categoriaId)
            ->where('nombre', $nombre)
            ->value('id');
    }

    private function quetzales(int $centavos): string
    {
        return sprintf('%d.%02d', intdiv($centavos, 100), $centavos % 100);
    }
}
