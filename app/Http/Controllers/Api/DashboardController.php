<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ResumenAnualRequest;
use App\Http\Requests\Dashboard\ResumenRequest;
use App\Support\Dinero;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Endpoints de agregacion del dashboard.
 *
 * Todos parten de $request->user()->ingresos() / ->egresos(), asi el filtro
 * por usuario autenticado viene incluido en la relacion y no depende de
 * recordar un where.
 *
 * Ninguna consulta usa YEAR() ni MONTH() en el WHERE: el periodo se acota
 * siempre con un rango semiabierto [inicio, fin) sobre `fecha`, que es lo
 * unico que permite a MySQL usar el indice (user_id, fecha).
 */
class DashboardController extends Controller
{
    /**
     * GET /api/dashboard/resumen?anio=&mes=
     *
     * Dos consultas en total, una por tabla. Cada una devuelve de un solo
     * golpe el total del mes, el acumulado de enero al mes indicado y el
     * total del mes anterior, usando SUM condicional.
     */
    public function resumen(ResumenRequest $request): JsonResponse
    {
        $anio = (int) $request->validated('anio');
        $mes = (int) $request->validated('mes');

        $inicioMes = CarbonImmutable::createFromDate($anio, $mes, 1)->startOfMonth();
        $finMes = $inicioMes->addMonth();
        $inicioAnio = $inicioMes->startOfYear();
        $inicioMesAnterior = $inicioMes->subMonth();

        // En enero, el mes anterior cae en el anio anterior y queda fuera del
        // rango del acumulado. Se amplia el limite inferior para que una sola
        // consulta por tabla cubra los tres periodos.
        $desde = $inicioMesAnterior->lessThan($inicioAnio) ? $inicioMesAnterior : $inicioAnio;

        $ingresos = $this->totales(
            $request->user()->ingresos(), $desde, $inicioMes, $finMes, $inicioAnio, $inicioMesAnterior,
        );

        $egresos = $this->totales(
            $request->user()->egresos(), $desde, $inicioMes, $finMes, $inicioAnio, $inicioMesAnterior,
        );

        return response()->json([
            'anio' => $anio,
            'mes' => $mes,

            'ingresos_mes' => $ingresos['mes'],
            'egresos_mes' => $egresos['mes'],
            'balance_mes' => Dinero::restar($ingresos['mes'], $egresos['mes']),

            'ingresos_acumulados' => $ingresos['acumulado'],
            'egresos_acumulados' => $egresos['acumulado'],
            'balance_acumulado' => Dinero::restar($ingresos['acumulado'], $egresos['acumulado']),

            'porcentaje_gastado' => Dinero::porcentaje($egresos['mes'], $ingresos['mes']),

            // Complemento: comparacion contra el mes anterior.
            'comparacion' => [
                'ingresos_mes_anterior' => $ingresos['anterior'],
                'egresos_mes_anterior' => $egresos['anterior'],
                'variacion_ingresos' => $this->variacion($ingresos['anterior'], $ingresos['mes']),
                'variacion_egresos' => $this->variacion($egresos['anterior'], $egresos['mes']),
            ],

            'consultas' => 2,
        ]);
    }

    /**
     * GET /api/dashboard/egresos-por-categoria?anio=&mes=
     *
     * Una sola consulta. El JOIN interno excluye por si mismo las categorias
     * sin egresos en el periodo, sin necesidad de un HAVING.
     */
    public function egresosPorCategoria(ResumenRequest $request): JsonResponse
    {
        $anio = (int) $request->validated('anio');
        $mes = (int) $request->validated('mes');

        $inicio = CarbonImmutable::createFromDate($anio, $mes, 1)->startOfMonth();

        $filas = $request->user()->egresos()
            ->join('categorias', 'categorias.id', '=', 'egresos.categoria_id')
            ->where('egresos.fecha', '>=', $inicio->toDateString())
            ->where('egresos.fecha', '<', $inicio->addMonth()->toDateString())
            ->groupBy('categorias.id', 'categorias.nombre')
            ->orderByDesc('total')
            // El id desempata categorias con el mismo total, para que el orden
            // sea estable entre llamadas.
            ->orderBy('categorias.id')
            ->get([
                'categorias.id as categoria_id',
                'categorias.nombre as categoria',
                DB::raw('SUM(egresos.monto) as total'),
            ]);

        $total = $filas->reduce(
            fn (string $acumulado, $fila) => bcadd($acumulado, Dinero::normalizar($fila->total), 2),
            Dinero::CERO,
        );

        return response()->json([
            'anio' => $anio,
            'mes' => $mes,
            'total' => $total,
            'data' => $filas->map(fn ($fila) => [
                'categoria_id' => (int) $fila->categoria_id,
                'categoria' => $fila->categoria,
                'total' => Dinero::normalizar($fila->total),
                'porcentaje' => Dinero::porcentaje(Dinero::normalizar($fila->total), $total),
            ])->all(),
            'consultas' => 1,
        ]);
    }

    /**
     * GET /api/dashboard/resumen-anual?anio=
     *
     * Dos consultas. Devuelve siempre doce elementos: los meses sin registros
     * se rellenan en PHP, no se piden a MySQL.
     */
    public function resumenAnual(ResumenAnualRequest $request): JsonResponse
    {
        $anio = (int) $request->validated('anio');

        $ingresosPorMes = $this->totalesPorMes($request->user()->ingresos(), $anio);
        $egresosPorMes = $this->totalesPorMes($request->user()->egresos(), $anio);

        $meses = [];

        foreach (range(1, 12) as $mes) {
            // El ?? es lo que resuelve el mes vacio: MySQL no devuelve fila
            // para un mes sin registros, y aqui se sustituye por 0.00.
            $ingresos = $ingresosPorMes[$mes] ?? Dinero::CERO;
            $egresos = $egresosPorMes[$mes] ?? Dinero::CERO;

            $meses[] = [
                'mes' => $mes,
                'nombre' => CarbonImmutable::createFromDate($anio, $mes, 1)->translatedFormat('F'),
                'ingresos' => $ingresos,
                'egresos' => $egresos,
                'balance' => Dinero::restar($ingresos, $egresos),
            ];
        }

        return response()->json([
            'anio' => $anio,
            'data' => $meses,
            'consultas' => 2,
        ]);
    }

    /**
     * Mes, acumulado y mes anterior en una sola pasada, con SUM condicional.
     *
     * El WHERE acota el rango completo y usa el indice; los CASE solo deciden
     * en que cubeta cae cada fila ya leida.
     *
     * @return array{mes: string, acumulado: string, anterior: string}
     */
    private function totales(
        HasMany $relacion,
        CarbonImmutable $desde,
        CarbonImmutable $inicioMes,
        CarbonImmutable $finMes,
        CarbonImmutable $inicioAnio,
        CarbonImmutable $inicioMesAnterior,
    ): array {
        $sumaCondicional = 'COALESCE(SUM(CASE WHEN fecha >= ? AND fecha < ? THEN monto END), 0)';

        $fila = $relacion
            ->selectRaw(
                $sumaCondicional.' AS total_mes, '
                .$sumaCondicional.' AS total_acumulado, '
                .$sumaCondicional.' AS total_anterior',
                [
                    $inicioMes->toDateString(), $finMes->toDateString(),
                    $inicioAnio->toDateString(), $finMes->toDateString(),
                    $inicioMesAnterior->toDateString(), $inicioMes->toDateString(),
                ],
            )
            ->where('fecha', '>=', $desde->toDateString())
            ->where('fecha', '<', $finMes->toDateString())
            ->first();

        return [
            'mes' => Dinero::normalizar($fila?->total_mes),
            'acumulado' => Dinero::normalizar($fila?->total_acumulado),
            'anterior' => Dinero::normalizar($fila?->total_anterior),
        ];
    }

    /**
     * @return array<int, string> numero de mes (1-12) => total
     */
    private function totalesPorMes(HasMany $relacion, int $anio): array
    {
        $inicio = CarbonImmutable::createFromDate($anio, 1, 1)->startOfYear();

        return $relacion
            // MONTH() aparece solo en el SELECT y el GROUP BY. El WHERE sigue
            // siendo un rango, asi que el indice (user_id, fecha) se usa igual.
            ->selectRaw('MONTH(fecha) AS numero_mes, COALESCE(SUM(monto), 0) AS total')
            ->where('fecha', '>=', $inicio->toDateString())
            ->where('fecha', '<', $inicio->addYear()->toDateString())
            ->groupBy(DB::raw('MONTH(fecha)'))
            ->get()
            ->mapWithKeys(fn ($fila) => [(int) $fila->numero_mes => Dinero::normalizar($fila->total)])
            ->all();
    }

    /**
     * Variacion porcentual entre dos periodos.
     *
     * Devuelve null cuando la base es cero: pasar de Q0 a Q500 no es un
     * aumento del 0% ni del 100%, es indefinido. El null obliga al frontend a
     * mostrar "sin dato" en vez de un numero falso.
     */
    private function variacion(string $anterior, string $actual): ?string
    {
        if (bccomp($anterior, '0', 2) === 0) {
            return null;
        }

        return Dinero::porcentaje(Dinero::restar($actual, $anterior), $anterior);
    }
}
