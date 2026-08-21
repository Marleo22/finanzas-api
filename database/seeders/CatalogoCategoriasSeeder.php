<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoCategoriasSeeder extends Seeder
{
    /**
     * Catalogo del sistema (user_id NULL). Es idempotente: correrlo dos veces
     * no duplica nada, porque usa firstOrCreate contra la llave logica.
     */
    private const CATEGORIAS = [
        'ingreso' => [
            'Empleo', 'Freelance / Proyecto', 'Negocio Propio',
            'Inversion / Dividendos', 'Bono / Extra', 'Otro Ingreso',
        ],
        'egreso' => [
            'Vivienda', 'Educacion', 'Alimentacion', 'Transporte', 'Salud',
            'Ocio / Entretenimiento', 'Deporte', 'Imprevistos', 'Otro Egreso',
        ],
    ];

    private const SUBCATEGORIAS = [
        'Vivienda'               => ['Alquiler', 'Agua', 'Luz', 'Internet'],
        'Educacion'              => ['Universidad', 'Cursos', 'Libros'],
        'Alimentacion'           => ['Supermercado', 'Restaurante', 'Almuerzo'],
        'Transporte'             => ['Gasolina', 'Bus', 'Taxi / Uber', 'Parqueo'],
        'Salud'                  => ['Consulta', 'Medicamentos', 'Laboratorio'],
        'Ocio / Entretenimiento' => ['Suscripciones', 'Cine', 'Salidas'],
        'Deporte'                => ['Gimnasio', 'Equipo deportivo'],
        'Imprevistos'            => ['Emergencias', 'Reparaciones'],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $ids = [];

            foreach (self::CATEGORIAS as $tipo => $nombres) {
                foreach ($nombres as $nombre) {
                    $ids[$nombre] = $this->firstOrCreate('categorias', [
                        'user_id' => null,
                        'nombre'  => $nombre,
                        'tipo'    => $tipo,
                    ]);
                }
            }

            foreach (self::SUBCATEGORIAS as $categoria => $nombres) {
                foreach ($nombres as $nombre) {
                    $this->firstOrCreate('subcategorias', [
                        'categoria_id' => $ids[$categoria],
                        'user_id'      => null,
                        'nombre'       => $nombre,
                    ]);
                }
            }
        });
    }

    private function firstOrCreate(string $tabla, array $atributos): int
    {
        $existente = DB::table($tabla)->where($atributos)->value('id');

        if ($existente !== null) {
            return $existente;
        }

        return DB::table($tabla)->insertGetId($atributos + [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
