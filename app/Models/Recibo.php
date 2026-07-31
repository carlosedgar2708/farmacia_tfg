<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recibo extends Model
{
    use HasFactory;

    protected $table = 'recibos';

    protected $fillable = [
        'venta_id',
        'monto',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
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
