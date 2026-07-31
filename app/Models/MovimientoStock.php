<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoStock extends Model
{
    use HasFactory;

    // Nombre real de la tabla
    protected $table = 'movimientos_stock';

    // Campos que pueden llenarse masivamente
    protected $fillable = [
        'lote_id',
        'fecha',
        'tipo',              // compra | venta | devolucion | ajuste
        'motivo',
        'cantidad',          // positiva o negativa
        'referencia',   // nombre del modelo origen (Compra, Venta, etc.
    ];

    // Conversión de tipos
    protected $casts = [
        'fecha'=> 'datetime',
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
     * Filtra los movimientos que son entradas.
     * Corregido (BUG-06): filtraba por el signo de `cantidad`, que
     * `CompraController`/`VentaController` siempre guardan positivo —
     * ambos scopes devolvían los mismos datos. Se filtra por `tipo`,
     * la columna que sí distingue entradas de salidas.
     */
    public function scopeEntradas($query)
    {
        return $query->where('tipo', 'Entrada');
    }

    /**
     * Filtra los movimientos que son salidas (ver nota en scopeEntradas).
     */
    public function scopeSalidas($query)
    {
        return $query->where('tipo', 'Salida');
    }
}
