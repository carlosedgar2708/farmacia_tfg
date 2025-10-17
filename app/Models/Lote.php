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
}
