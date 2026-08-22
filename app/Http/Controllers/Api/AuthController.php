<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Hash bcrypt valido de un valor que nadie usa. Sirve para gastar el mismo
     * tiempo de comparacion cuando el correo no existe. Tiene que ser un hash
     * real: BcryptHasher lanza RuntimeException si el formato no es bcrypt.
     */
    private const HASH_SEÑUELO = '$2y$12$g5dz5wRZzbxJwbvs9k/BFO2ZG32C.KIjZuLecbSGWM7YZiMtBLppm';

    /**
     * Emite un token Bearer de Sanctum.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        // Un solo mensaje para credenciales malas, sin distinguir si el correo
        // existe: lo contrario permite averiguar que cuentas estan registradas.
        // Hash::check se ejecuta siempre aunque el usuario no exista, para que
        // el tiempo de respuesta no delate la diferencia.
        $valido = Hash::check(
            $request->validated('password'),
            $user?->password ?? self::HASH_SEÑUELO,
        );

        if ($user === null || ! $valido) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son correctas.',
            ]);
        }

        $token = $user->createToken($request->validated('device_name') ?? 'api');

        return response()->json([
            'token' => $token->plainTextToken,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Revoca unicamente el token con el que se hizo esta peticion.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesion cerrada.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'id'    => $request->user()->id,
            'name'  => $request->user()->name,
            'email' => $request->user()->email,
        ]);
    }
}
