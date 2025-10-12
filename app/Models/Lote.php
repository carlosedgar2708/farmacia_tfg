<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Lote extends Model
{
    use HasFactory;

    // Nombre exacto de la tabla
    protected $table = 'lotes';

    // Campos asignables
    protected $fillable = [
        'producto_id',
        'codigo',             // si tu migración lo tiene (código/lote)
        'fecha_vencimiento',
    ];

    // Casts
    protected $casts = [
        'fecha_vencimiento' => 'date',
    ];

    // ------------------ RELACIONES ------------------

    // Cada lote pertenece a un producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    // Movimientos de stock asociados al lote (entradas/salidas)
    public function movimientos()
    {
        return $this->hasMany(MovimientoStock::class, 'lote_id');
    }

    // (Opcional) si guardas el lote en detalles de compra/venta:
    public function detallesCompra()
    {
        return $this->hasMany(DetalleCompra::class, 'lote_id');
    }

    public function detallesVenta()
    {
        return $this->hasMany(DetalleVenta::class, 'lote_id');
    }

    // ------------------ SCOPES FEFO ------------------

    // Solo lotes no vencidos (hoy no cuenta como vencido)
    public function scopeNoVencidos($q)
    {
        return $q->whereDate('fecha_vencimiento', '>', Carbon::today());
    }

    // Orden por fecha de vencimiento ascendente (FEFO)
    public function scopeOrdenVencimiento($q)
    {
        return $q->orderBy('fecha_vencimiento', 'asc');
    }

    // Solo lotes con stock disponible (> 0)
    public function scopeConStock($q)
    {
        // Usa having sum(cantidad) > 0 sobre movimientos
        return $q->whereHas('movimientos', function ($qq) {
            $qq->selectRaw('lote_id, SUM(cantidad) as s')
               ->groupBy('lote_id')
               ->havingRaw('SUM(cantidad) > 0');
        });
    }

    // ------------------ CAMPOS DERIVADOS ------------------

    // Stock disponible del lote (entradas - salidas)
    public function getStockDisponibleAttribute(): int
    {
        return (int) $this->movimientos()->sum('cantidad');
    }
}
