<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recibo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'recibos';

    // Ajusta estos campos a lo que tengas en tu migración de recibos
    protected $fillable = [
        'venta_id',
        'nro_recibo',   // opcional: correlativo del comprobante
        'fecha',        // datetime/date según tu migración
        'metodo_pago',  // efectivo, tarjeta, etc.
        'observacion',
        'estado',       // emitido, anulado, etc.
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    /* ------------------ RELACIONES ------------------ */

    // Un recibo pertenece a una venta
    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    /* ------------------ CAMPOS DERIVADOS ------------------ */

    // Total derivado: suma de (cantidad * precio_unitario) de los detalles de la venta
    public function getTotalAttribute(): float
    {
        $venta = $this->relationLoaded('venta')
            ? $this->venta
            : $this->venta()->with('detalles')->first();

        if (!$venta) return 0.0;

        return (float) $venta->detalles->sum(
            fn ($d) => (int)$d->cantidad * (float)$d->precio_unitario
        );
    }
}
