<?php

namespace App\Support;

/**
 * Aritmetica de dinero sobre strings, con bcmath.
 *
 * Los montos llegan de MySQL como string (decimal(12,2) via PDO) y salen al
 * JSON como string. En medio hay que restarlos y dividirlos: hacerlo con los
 * operadores de PHP los convertiria a float y reintroduciria el error de
 * precision que decimal(12,2) evita en la base.
 */
final class Dinero
{
    public const CERO = '0.00';

    /**
     * Normaliza cualquier valor a string con dos decimales.
     * COALESCE de MySQL puede devolver '0' o un decimal con mas escala.
     */
    public static function normalizar(string|int|float|null $valor): string
    {
        return bcadd((string) ($valor ?? 0), '0', 2);
    }

    public static function restar(string $a, string $b): string
    {
        return bcsub($a, $b, 2);
    }

    /**
     * Que porcentaje representa $parte de $total.
     *
     * Si $total es cero devuelve 0.00 en lugar de dividir entre cero. Es una
     * convencion, no una verdad matematica: gastar Q500 sin ingresos no es
     * "0% gastado". El frontend deberia mostrar un guion cuando el ingreso
     * del mes sea cero.
     */
    public static function porcentaje(string $parte, string $total): string
    {
        if (bccomp($total, '0', 2) === 0) {
            return self::CERO;
        }

        return self::redondear(bcdiv(bcmul($parte, '100', 4), $total, 4), 2);
    }

    /**
     * bcmath trunca en vez de redondear; esto redondea al medio hacia arriba.
     */
    public static function redondear(string $numero, int $decimales): string
    {
        $mitad = '0.'.str_repeat('0', $decimales).'5';

        $ajustado = bccomp($numero, '0', $decimales + 2) >= 0
            ? bcadd($numero, $mitad, $decimales + 1)
            : bcsub($numero, $mitad, $decimales + 1);

        return bcadd($ajustado, '0', $decimales);
    }
}
