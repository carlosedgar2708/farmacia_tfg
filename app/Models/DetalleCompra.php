<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetalleCompra extends Model
{
    use HasFactory, SoftDeletes;

    // Nombre real de la tabla (porque Laravel asumiría "detalle_compras")
    protected $table = 'detalles_compra';

    // Campos que se pueden llenar con create() o update()
    protected $fillable = [
        'compra_id',
        'producto_id',
        'lote_id',          // si tu detalle referencia el lote
        'cantidad',
        'precio_unitario',            // opcional, si está en tu tabla
    ];

    // Conversión automática de tipos
    protected $casts = [
        'cantidad'        => 'integer',
        'precio_unitario' => 'decimal:2',
    ];

    // ---------------------------------------------------------
    // 🔗 RELACIONES
    // ---------------------------------------------------------

    /**
     * Un detalle pertenece a una compra.
     */
    public function compra()
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    /**
     * Un detalle pertenece a un producto.
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /**
     * Un detalle puede estar asociado a un lote.
     */
    public function lote()
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    // ---------------------------------------------------------
    // 💡 ACCESORES / CAMPOS DERIVADOS
    // ---------------------------------------------------------

    /**
     * Calcula el subtotal del detalle (cantidad * precio_unitario)
     */
    public function getSubtotalAttribute(): float
    {
        return (float) ($this->cantidad * $this->precio_unitario);
    }
}
