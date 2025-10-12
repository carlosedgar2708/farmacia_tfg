<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Devolucion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'devolucions'; // acorde a tu migración

    protected $fillable = [
        'venta_id',         // devuelve contra una venta (trazabilidad)
        'cliente_id',       // quién devuelve
        'user_id',          // usuario del sistema que registró
        'fecha_devolucion',
        'motivo',           // opcional: “defecto”, “cadena de frío”, etc.
        'observacion',
        'estado',           // ej: 'borrador','aprobada','anulada'
    ];

    protected $casts = [
        'fecha_devolucion' => 'datetime',
    ];

    /* ---------------- RELACIONES ---------------- */

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleDevolucion::class, 'devolucion_id');
    }

    // Rastro de stock generado por esta devolución (entradas positivas)
    public function movimientosStock()
    {
        return $this->hasMany(MovimientoStock::class, 'referencia_id')
                    ->where('referencia_tipo', 'Devolucion');
    }

    /* -------------- CAMPOS DERIVADOS -------------- */

    // Total retornado (si manejas precio en detalle de devolución)
    public function getTotalRetornadoAttribute(): float
    {
        return (float) $this->detalles->sum(
            fn ($d) => (int)$d->cantidad * (float)($d->precio_unitario ?? 0)
        );
    }
}
