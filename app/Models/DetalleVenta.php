<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetalleVenta extends Model
{
    use HasFactory, SoftDeletes;

    // Nombre exacto de la tabla
    protected $table = 'detalles_venta';

    // Campos asignables
    protected $fillable = [
        'venta_id',
        'producto_id',
        'lote_id',          // lote del que se vendió
        'cantidad',
        'precio_unitario',
    ];

    // Casts
    protected $casts = [
        'cantidad'        => 'integer',
        'precio_unitario' => 'decimal:2',
    ];

    /* ------------------ RELACIONES ------------------ */

    // Cada detalle pertenece a una venta
    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    // Cada detalle pertenece a un producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    // Cada detalle se asocia a un lote específico
    public function lote()
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    /* ------------------ CAMPOS DERIVADOS ------------------ */

    // Subtotal = cantidad * precio_unitario
    public function getSubtotalAttribute(): float
    {
        return (float) ($this->cantidad * $this->precio_unitario);
    }
}
