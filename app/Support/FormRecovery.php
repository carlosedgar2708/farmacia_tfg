<?php

namespace App\Support;

/**
 * Helpers para reconstruir formularios con filas dinámicas (Compras, Ventas,
 * stock de Lotes) tras una redirección por error de validación, sin repetir
 * en cada controlador la lógica de resolver old() + nombres legibles.
 *
 * Ver docs/pendientes.md (PEND-08) para el diseño completo.
 */
class FormRecovery
{
    /**
     * old($key, []) enriquecido: agrega "<campo>_label" resolviendo el id de
     * cada fila contra el modelo indicado en $lookups, en una sola consulta
     * por campo (whereIn), no una por fila.
     *
     * @param  string  $key  Clave de old(), ej. "items" o "lotes".
     * @param  array<string,class-string<\Illuminate\Database\Eloquent\Model>>  $lookups
     *         Ej. ['producto_id' => \App\Models\Producto::class] agrega "producto_id_label".
     */
    public static function items(string $key, array $lookups = [], string $labelColumn = 'nombre'): array
    {
        $rows = old($key, []);

        if (empty($rows) || empty($lookups)) {
            return $rows;
        }

        foreach ($lookups as $field => $modelClass) {
            $ids = collect($rows)->pluck($field)->filter()->unique()->values();
            $labels = $ids->isEmpty()
                ? collect()
                : $modelClass::whereIn('id', $ids)->pluck($labelColumn, 'id');

            foreach ($rows as $i => $row) {
                $rows[$i][$field.'_label'] = $labels[$row[$field] ?? null] ?? null;
            }
        }

        return $rows;
    }

    /**
     * Nombre legible de un campo old() simple (ej. proveedor_id, cliente_id)
     * que no es una fila dinámica sino un único valor de cabecera.
     */
    public static function label(string $key, string $modelClass, string $labelColumn = 'nombre'): ?string
    {
        $id = old($key);

        if (! $id) {
            return null;
        }

        return $modelClass::find($id)?->{$labelColumn};
    }

    /**
     * Errores de campo de la sesión, en la misma forma que $errors->messages()
     * (ej. "items.0.cantidad" => ["Stock insuficiente..."]), sin depender de
     * que la vista reciba $errors directamente.
     */
    public static function fieldErrors(): array
    {
        $errors = session('errors');

        return $errors ? $errors->getBag('default')->messages() : [];
    }
}
