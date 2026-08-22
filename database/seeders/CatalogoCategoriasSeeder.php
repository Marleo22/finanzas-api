<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catalogo de categorias del sistema (user_id null), visible para todos.
 *
 * Es idempotente: correrlo N veces deja el mismo resultado que correrlo una.
 */
class CatalogoCategoriasSeeder extends Seeder
{
    private const INGRESOS = [
        'Empleo',
        'Freelance / Proyecto',
        'Negocio Propio',
        'Inversión / Dividendos',
        'Bono / Extra',
        'Otro Ingreso',
    ];

    /** Categoria => subcategorias. Lista vacia = sin subcategorias. */
    private const EGRESOS = [
        'Vivienda'               => ['Alquiler', 'Agua', 'Luz', 'Internet'],
        'Educación'              => ['Universidad', 'Cursos', 'Libros'],
        'Alimentación'           => ['Supermercado', 'Restaurante', 'Almuerzo'],
        'Transporte'             => ['Gasolina', 'Bus', 'Taxi / Uber', 'Parqueo'],
        'Salud'                  => ['Consulta', 'Medicamentos', 'Laboratorio'],
        'Ocio / Entretenimiento' => ['Suscripciones', 'Cine', 'Salidas'],
        'Deporte'                => ['Gimnasio', 'Equipo deportivo'],
        'Imprevistos'            => ['Emergencias', 'Reparaciones'],
        'Otro Egreso'            => [],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::INGRESOS as $nombre) {
                $this->categoria($nombre, 'ingreso');
            }

            foreach (self::EGRESOS as $nombre => $subcategorias) {
                $categoriaId = $this->categoria($nombre, 'egreso');

                foreach ($subcategorias as $subcategoria) {
                    $this->subcategoria($categoriaId, $subcategoria);
                }
            }
        });

        $this->command?->info(sprintf(
            'Catalogo del sistema: %d categorias, %d subcategorias.',
            DB::table('categorias')->whereNull('user_id')->count(),
            DB::table('subcategorias')->whereNull('user_id')->count(),
        ));
    }

    /**
     * Devuelve el id de la categoria del sistema, creandola solo si no existe.
     */
    private function categoria(string $nombre, string $tipo): int
    {
        $existente = DB::table('categorias')
            ->whereNull('user_id')
            ->where('nombre', $nombre)
            ->where('tipo', $tipo)
            ->first(['id', 'nombre']);

        if ($existente !== null) {
            // La colacion utf8mb4_unicode_ci ignora tildes al comparar, asi que
            // el where de arriba encuentra 'Educacion' cuando buscamos
            // 'Educación'. Se corrige el nombre para que quede el canonico.
            if ($existente->nombre !== $nombre) {
                DB::table('categorias')
                    ->where('id', $existente->id)
                    ->update(['nombre' => $nombre, 'updated_at' => now()]);
            }

            return $existente->id;
        }

        return DB::table('categorias')->insertGetId([
            'user_id'    => null,
            'nombre'     => $nombre,
            'tipo'       => $tipo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function subcategoria(int $categoriaId, string $nombre): void
    {
        $existente = DB::table('subcategorias')
            ->whereNull('user_id')
            ->where('categoria_id', $categoriaId)
            ->where('nombre', $nombre)
            ->first(['id', 'nombre']);

        if ($existente !== null) {
            if ($existente->nombre !== $nombre) {
                DB::table('subcategorias')
                    ->where('id', $existente->id)
                    ->update(['nombre' => $nombre, 'updated_at' => now()]);
            }

            return;
        }

        DB::table('subcategorias')->insert([
            'categoria_id' => $categoriaId,
            'user_id'      => null,
            'nombre'       => $nombre,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }
}
