<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'codigo',
        'nombre',
        'es_inyectable',
        'description',
        'precio_venta',
    ];

    protected $casts = [
        'es_inyectable' => 'boolean',
    ];

    public function lotes()
    {
        return $this->hasMany(Lote::class);
    }

    /**
     * Productos con al menos un lote vencido con stock, o un lote que vence
     * dentro de los próximos $dias días. Reutiliza los scopes de Lote.
     */
    public function scopeConAlertaVencimiento($query, int $dias)
    {
        return $query->where(function ($q) use ($dias) {
            $q->whereHas('lotes', fn ($l) => $l->where('stock', '>', 0)->vencidos())
              ->orWhereHas('lotes', fn ($l) => $l->where('stock', '>', 0)->proximosAVencer($dias));
        });
    }

    /**
     * Lote vencido con stock más próximo (el más urgente) de este producto.
     */
    public function loteVencidoRelevante()
    {
        return $this->lotes()->where('stock', '>', 0)->vencidos()->orderBy('fecha_vencimiento')->first();
    }

    /**
     * Lote vigente que vence antes dentro del umbral de días indicado.
     */
    public function loteProximoRelevante(int $dias)
    {
        return $this->lotes()->where('stock', '>', 0)->proximosAVencer($dias)->orderBy('fecha_vencimiento')->first();
    }
}
