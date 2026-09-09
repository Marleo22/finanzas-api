<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ingreso\IndexIngresoRequest;
use App\Http\Requests\Ingreso\StoreIngresoRequest;
use App\Http\Requests\Ingreso\UpdateIngresoRequest;
use App\Http\Resources\IngresoResource;
use App\Models\Ingreso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Todas las consultas parten de $request->user()->ingresos(), nunca de
 * Ingreso::query(). Asi el filtro por usuario autenticado no depende de
 * recordar un where en cada metodo: es el punto de partida obligatorio.
 */
class IngresoController extends Controller
{
    public function index(IndexIngresoRequest $request): AnonymousResourceCollection
    {
        $ingresos = $request->user()
            ->ingresos()
            // El ingreso no tiene subcategoria, asi que solo se precarga la
            // categoria. Sin el with, listar 25 ingresos dispararia 25
            // consultas extra, una por cada categoria (problema N+1).
            ->with('categoria')
            ->when(
                $request->tienePeriodo(),
                fn ($query) => $query->delMes(
                    (int) $request->integer('anio'),
                    (int) $request->integer('mes'),
                ),
            )
            // El id desempata los movimientos del mismo dia; sin el, el orden
            // entre paginas no es estable y una fila puede repetirse o
            // desaparecer al paginar.
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page') ?: 25)
            ->withQueryString();

        return IngresoResource::collection($ingresos);
    }

    public function store(StoreIngresoRequest $request): JsonResponse
    {
        // create() sobre la relacion asigna user_id solo. Aunque el request
        // trajera user_id, no esta en $fillable y se descarta.
        $ingreso = $request->user()->ingresos()->create($request->validated());

        $ingreso->load('categoria');

        return IngresoResource::make($ingreso)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, int $ingreso): IngresoResource
    {
        return IngresoResource::make(
            $this->buscar($request, $ingreso)->load('categoria'),
        );
    }

    public function update(UpdateIngresoRequest $request, int $ingreso): IngresoResource
    {
        $modelo = $this->buscar($request, $ingreso);

        $modelo->update($request->validated());

        return IngresoResource::make($modelo->load('categoria'));
    }

    public function destroy(Request $request, int $ingreso): Response
    {
        $this->buscar($request, $ingreso)->delete();

        return response()->noContent();
    }

    /**
     * Busca dentro de los ingresos del usuario autenticado.
     *
     * findOrFail sobre la relacion devuelve 404 tanto si el id no existe como
     * si pertenece a otra cuenta. Es deliberado: un 403 confirmaria que el
     * registro existe y filtraria informacion de otro usuario.
     */
    private function buscar(Request $request, int $id): Ingreso
    {
        return $request->user()->ingresos()->findOrFail($id);
    }
}
