<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Compra extends Model
{
    use HasFactory, SoftDeletes;

    // 🔹 Nombre de la tabla
    protected $table = 'compras';

    // 🔹 Campos que se pueden asignar masivamente
    protected $fillable = [
        'proveedor_id',
        'user_id',
        'fecha_compra',
        'num_factura',
        'observacion',
        'estado',
    ];

    // 🔹 Conversión de tipos
    protected $casts = [
        'fecha_compra' => 'date',
    ];

    // ----------------------------------------------------------
    // 🔹 Relaciones
    // ----------------------------------------------------------

    /**
     * Una compra pertenece a un proveedor.
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    /**
     * Una compra fue registrada por un usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Una compra tiene muchos detalles de compra.
     */
    public function detalles()
    {
        return $this->hasMany(DetalleCompra::class, 'compra_id');
    }

    /**
     * Una compra puede generar muchos movimientos de stock.
     */
    public function movimientosStock()
    {
        return $this->hasMany(MovimientoStock::class, 'referencia_id')
                    ->where('referencia_tipo', 'compra');
    }

    // ----------------------------------------------------------
    // 🔹 Accesores o helpers
    // ----------------------------------------------------------

    /**
     * Total derivado de la compra (suma de subtotales de detalles)
     */
    public function getTotalAttribute(): float
    {
        return $this->detalles->sum(fn ($d) => $d->cantidad * $d->precio_unitario);
    }
}
