<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Categoria\IndexCategoriaRequest;
use App\Http\Requests\Categoria\StoreCategoriaRequest;
use App\Http\Requests\Categoria\UpdateCategoriaRequest;
use App\Http\Resources\CategoriaResource;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Catalogo de categorias: las del sistema mas las propias del usuario.
 *
 * Esta es la unica parte de la API donde el filtro por usuario no es un
 * simple where user_id = X, porque las categorias del sistema tienen user_id
 * null. Toda la excepcion vive concentrada en el scope visiblesPara().
 */
class CategoriaController extends Controller
{
    public function index(IndexCategoriaRequest $request): AnonymousResourceCollection
    {
        $categorias = Categoria::visiblesPara($request->user()->id)
            ->when(
                $request->filled('tipo'),
                fn ($query) => $query->deTipo($request->string('tipo')->toString()),
            )
            // Sin el with, listar 16 categorias dispararia 16 consultas extra.
            ->with('subcategorias')
            // Las del sistema primero, y dentro de cada grupo por nombre.
            ->orderByRaw('user_id IS NOT NULL')
            ->orderBy('nombre')
            ->get();

        return CategoriaResource::collection($categorias);
    }

    public function store(StoreCategoriaRequest $request): JsonResponse
    {
        $categoria = $request->user()->categorias()->create($request->validated());

        return CategoriaResource::make($categoria->load('subcategorias'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateCategoriaRequest $request, int $categoria): CategoriaResource
    {
        $modelo = $this->buscarPropia($request, $categoria);

        $modelo->update($request->validated());

        return CategoriaResource::make($modelo->load('subcategorias'));
    }

    public function destroy(Request $request, int $categoria): JsonResponse|Response
    {
        $modelo = $this->buscarPropia($request, $categoria);

        // La llave foranea es RESTRICT, asi que MySQL rechazaria el borrado
        // con un error 500. Se comprueba antes para responder algo util.
        $enUso = $modelo->ingresos()->count() + $modelo->egresos()->count();

        if ($enUso > 0) {
            return response()->json([
                'message' => "No se puede borrar: hay {$enUso} movimientos usando esta categoria. Reasignalos primero.",
            ], Response::HTTP_CONFLICT);
        }

        $modelo->delete();

        return response()->noContent();
    }

    /**
     * Solo las categorias propias del usuario.
     *
     * No usa visiblesPara() a proposito: las del sistema son compartidas y
     * ningun usuario puede editarlas ni borrarlas. Devuelve 404 en ese caso,
     * igual que si la categoria no existiera.
     */
    private function buscarPropia(Request $request, int $id): Categoria
    {
        return $request->user()->categorias()->findOrFail($id);
    }
}
