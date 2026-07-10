<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lote extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lotes';

    protected $fillable = [
        'producto_id',
        'nro_lote',
        'fecha_vencimiento',
        'costo_unitario',
        'stock',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'costo_unitario'    => 'decimal:2',
        'stock'             => 'integer',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Lotes vendibles: sin fecha de vencimiento o con fecha futura/hoy.
     */
    public function scopeVigentes($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('fecha_vencimiento')
              ->orWhereDate('fecha_vencimiento', '>=', today());
        });
    }

    /**
     * Lotes ya vencidos.
     */
    public function scopeVencidos($query)
    {
        return $query->whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '<', today());
    }

    /**
     * Lotes vigentes cuya fecha de vencimiento cae dentro de los próximos $dias días.
     */
    public function scopeProximosAVencer($query, int $dias)
    {
        return $query->whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '>=', today())
            ->whereDate('fecha_vencimiento', '<=', today()->copy()->addDays($dias));
    }
}
