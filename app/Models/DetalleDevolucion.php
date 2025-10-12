<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetalleDevolucion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'detalles_devolucion';

    protected $fillable = [
        'devolucion_id',
        'producto_id',
        'lote_id',          // 🔴 reingresa al MISMO lote desde el que salió
        'cantidad',
        'precio_unitario',  // opcional, si calculas notas de crédito
        'razon',            // opcional: “vencido”, “dañado”, etc. (aunque tu regla dice NO vencidos en venta)
    ];

    protected $casts = [
        'cantidad'        => 'integer',
        'precio_unitario' => 'decimal:2',
    ];

    /* ---------------- RELACIONES ---------------- */

    public function devolucion()
    {
        return $this->belongsTo(Devolucion::class, 'devolucion_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function lote()
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    /* -------------- CAMPOS DERIVADOS -------------- */

    public function getSubtotalAttribute(): float
    {
        return (float) ($this->cantidad * (float)($this->precio_unitario ?? 0));
    }
}
