<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Compra extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'compras';

    protected $fillable = [
        'fecha',          // ← corregido
        'proveedor_id',
        'user_id',
        'observacion',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'datetime',   // ← corregido
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleCompra::class, 'compra_id');
    }

    public function movimientosStock()
    {
        return $this->hasMany(MovimientoStock::class, 'referencia_id')
                    ->where('referencia_tipo', 'compra');
    }

    public function getTotalAttribute(): float
    {
        return $this->detalles->sum(fn ($d) => $d->cantidad * $d->costo_unitario);
    }
}
