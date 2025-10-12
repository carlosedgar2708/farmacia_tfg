<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venta extends Model
{
    use HasFactory, SoftDeletes;

    // Nombre exacto de la tabla
    protected $table = 'ventas';

    // Campos asignables
    protected $fillable = [
        'cliente_id',
        'user_id',        // quién registró la venta
        'fecha_venta',
        'observacion',
        'estado',         // ej: 'borrador','confirmada','anulada'
    ];

    // Casts de tipos
    protected $casts = [
        'fecha_venta' => 'datetime',
    ];

    /* -------------------- RELACIONES -------------------- */

    // Venta pertenece a un cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    // Venta registrada por un usuario del sistema
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Detalles (items) de la venta
    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    // Recibo asociado (si tu diseño es 1 recibo : 1 venta)
    public function recibo()
    {
        return $this->hasOne(Recibo::class, 'venta_id');
    }

    // Movimientos de stock vinculados a esta venta (rastro)
    public function movimientosStock()
    {
        return $this->hasMany(MovimientoStock::class, 'referencia_id')
                    ->where('referencia_tipo', 'Venta');
    }

    /* -------------------- CAMPOS DERIVADOS -------------------- */

    // Total derivado (no se guarda en BD)
    public function getTotalAttribute(): float
    {
        // suma cantidad * precio_unitario de los detalles
        return (float) $this->detalles->sum(
            fn ($d) => (int)$d->cantidad * (float)$d->precio_unitario
        );
    }

    /* -------------------- SCOPES ÚTILES -------------------- */

    // Ventas confirmadas
    public function scopeConfirmadas($q)
    {
        return $q->where('estado', 'confirmada');
    }

    // Ventas del día
    public function scopeDelDia($q)
    {
        return $q->whereDate('fecha_venta', now()->toDateString());
    }
}
