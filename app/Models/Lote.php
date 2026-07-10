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
     * Valor del stock de este lote (stock actual x costo unitario). Mismo
     * patrón de accessor derivado que Venta::getTotalAttribute() y
     * DetalleCompra::getSubtotalAttribute().
     */
    public function getValorAttribute(): float
    {
        return (float) ($this->stock * $this->costo_unitario);
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

    /**
     * Lotes cuya fecha de vencimiento cae dentro de un rango arbitrario [$desde, $hasta].
     * A diferencia de proximosAVencer(), no ancla el límite inferior a "hoy" — para
     * reportes que permiten explorar cualquier rango elegido por el usuario.
     */
    public function scopeVenceEntre($query, $desde, $hasta)
    {
        return $query->whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '>=', $desde)
            ->whereDate('fecha_vencimiento', '<=', $hasta);
    }
}
