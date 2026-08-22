<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Egreso\IndexEgresoRequest;
use App\Http\Requests\Egreso\StoreEgresoRequest;
use App\Http\Requests\Egreso\UpdateEgresoRequest;
use App\Http\Resources\EgresoResource;
use App\Models\Egreso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Todas las consultas parten de $request->user()->egresos(), nunca de
 * Egreso::query(). Asi el filtro por usuario autenticado no depende de
 * recordar un where en cada metodo: es el punto de partida obligatorio.
 */
class EgresoController extends Controller
{
    public function index(IndexEgresoRequest $request): AnonymousResourceCollection
    {
        $egresos = $request->user()
            ->egresos()
            ->with(['categoria', 'subcategoria'])
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

        return EgresoResource::collection($egresos);
    }

    public function store(StoreEgresoRequest $request): JsonResponse
    {
        // create() sobre la relacion asigna user_id solo. Aunque el request
        // trajera user_id, no esta en $fillable y se descarta.
        $egreso = $request->user()->egresos()->create($request->validated());

        $egreso->load(['categoria', 'subcategoria']);

        return EgresoResource::make($egreso)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, int $egreso): EgresoResource
    {
        return EgresoResource::make(
            $this->buscar($request, $egreso)->load(['categoria', 'subcategoria']),
        );
    }

    public function update(UpdateEgresoRequest $request, int $egreso): EgresoResource
    {
        $modelo = $this->buscar($request, $egreso);

        $modelo->update($request->validated());

        return EgresoResource::make($modelo->load(['categoria', 'subcategoria']));
    }

    public function destroy(Request $request, int $egreso): Response
    {
        $this->buscar($request, $egreso)->delete();

        return response()->noContent();
    }

    /**
     * Busca dentro de los egresos del usuario autenticado.
     *
     * findOrFail sobre la relacion devuelve 404 tanto si el id no existe como
     * si pertenece a otra cuenta. Es deliberado: un 403 confirmaria que el
     * registro existe y filtraria informacion de otro usuario.
     */
    private function buscar(Request $request, int $id): Egreso
    {
        return $request->user()->egresos()->findOrFail($id);
    }
}
