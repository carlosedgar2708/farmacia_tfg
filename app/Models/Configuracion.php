<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Configuracion extends Model
{
    protected $table = 'configuraciones';

    protected $fillable = ['clave', 'valor', 'descripcion'];

    /**
     * Único punto de lectura de una configuración. Cacheada indefinidamente
     * hasta que establecer() invalide la entrada correspondiente.
     */
    public static function obtener(string $clave, $default = null)
    {
        $valor = Cache::rememberForever(
            "configuracion.{$clave}",
            fn () => static::where('clave', $clave)->value('valor')
        );

        return $valor ?? $default;
    }

    /**
     * Único punto de escritura de una configuración.
     */
    public static function establecer(string $clave, $valor): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor]);

        Cache::forget("configuracion.{$clave}");
    }
}
