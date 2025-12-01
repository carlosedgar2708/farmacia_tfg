<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// OJO: solo deja SoftDeletes si agregaste deleted_at a la migración
// use Illuminate\Database\Eloquent\SoftDeletes;

class DetalleCompra extends Model
{
    use HasFactory;
    // use SoftDeletes;

    protected $table = 'detalles_compra';

    protected $fillable = [
        'compra_id',
        'lote_id',
        'cantidad',
        'costo_unitario',
    ];

    protected $casts = [
        'cantidad'       => 'integer',
        'costo_unitario' => 'decimal:2',
    ];

    /* ───────────── Relaciones ───────────── */

    public function compra()
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    public function lote()
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    // Si quieres obtener producto desde el lote:
    public function producto()
    {
        return $this->hasOneThrough(
            Producto::class,
            Lote::class,
            'id',          // FK en lotes...
            'id',          // FK en productos...
            'lote_id',     // local key en detalles_compra
            'producto_id'  // local key en lotes
        );
    }

    /* ───────────── Accesor subtotal ───────────── */

    public function getSubtotalAttribute(): float
    {
        return (float) ($this->cantidad * $this->costo_unitario);
    }
}
