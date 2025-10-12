<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory; // Agrega SoftDeletes si tu tabla tiene deleted_at

    // Nombre exacto de la tabla
    protected $table = 'productos';

    // Campos asignables
    protected $fillable = [
        'nombre',
        'codigo',          // SKU o código interno
        'descripcion',
        'precio_compra',
        'precio_venta',
        'iva',
        'inyectable',      // true/false (para reglas de devoluciones)
        'activo',          // visible para venta/listado
    ];

    // Casts de tipos
    protected $casts = [
        'precio_compra' => 'decimal:2',
        'precio_venta'  => 'decimal:2',
        'iva'           => 'decimal:2',
        'inyectable'    => 'boolean',
        'activo'        => 'boolean',
    ];

    // ------------------ RELACIONES ------------------

    // Un producto tiene muchos lotes
    public function lotes()
    {
        return $this->hasMany(Lote::class, 'producto_id');
    }

    // Si tus detalles guardan producto_id:
    public function detallesCompra()
    {
        return $this->hasMany(DetalleCompra::class, 'producto_id');
    }

    public function detallesVenta()
    {
        return $this->hasMany(DetalleVenta::class, 'producto_id');
    }

    // ------------------ SCOPES ÚTILES ------------------

    public function scopeActivos($q)
    {
        return $q->where('activo', true);
    }

    public function scopePorCodigo($q, string $codigo)
    {
        return $q->where('codigo', $codigo);
    }
}
