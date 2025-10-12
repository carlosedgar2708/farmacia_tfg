<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovimientoStock extends Model
{
    use HasFactory, SoftDeletes;

    // Nombre real de la tabla
    protected $table = 'movimientos_stock';

    // Campos que pueden llenarse masivamente
    protected $fillable = [
        'lote_id',
        'tipo',              // compra | venta | devolucion | ajuste
        'cantidad',          // positiva o negativa
        'referencia_tipo',   // nombre del modelo origen (Compra, Venta, etc.)
        'referencia_id',     // id del registro origen
        'user_id',
        'observacion',
    ];

    // Conversión de tipos
    protected $casts = [
        'cantidad' => 'integer',
    ];

    // ---------------------------------------------------------
    // 🔗 RELACIONES
    // ---------------------------------------------------------

    /**
     * Un movimiento pertenece a un lote.
     */
    public function lote()
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    /**
     * Un movimiento fue realizado por un usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Un movimiento está asociado a una transacción origen (compra, venta, devolución, ajuste).
     * Esto permite rastrear el movimiento.
     */
    public function referencia()
    {
        return $this->morphTo(__FUNCTION__, 'referencia_tipo', 'referencia_id');
    }

    // ---------------------------------------------------------
    // 💡 SCOPES ÚTILES
    // ---------------------------------------------------------

    /**
     * Filtra los movimientos que son entradas (cantidad > 0)
     */
    public function scopeEntradas($query)
    {
        return $query->where('cantidad', '>', 0);
    }

    /**
     * Filtra los movimientos que son salidas (cantidad < 0)
     */
    public function scopeSalidas($query)
    {
        return $query->where('cantidad', '<', 0);
    }
}
