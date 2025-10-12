<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    // 🔹 Nombre de la tabla (por si acaso no sigue plural inglés)
    protected $table = 'clientes';

    // 🔹 Campos que se pueden llenar con create() o update()
    protected $fillable = [
        'nombre',
        'apellido',
        'dni',
        'telefono',
        'email',
        'direccion',
        'activo',
    ];

    // 🔹 Conversión automática de tipos
    protected $casts = [
        'activo' => 'boolean',
    ];

    // 🔹 Relaciones ---------------------------------------------------

    /**
     * Un cliente puede tener muchas ventas
     */
    public function ventas()
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }

    /**
     * Un cliente puede tener muchas devoluciones
     */
    public function devoluciones()
    {
        return $this->hasMany(Devolucion::class, 'cliente_id');
    }
}
