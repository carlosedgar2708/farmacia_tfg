<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'proveedors'; // 👈 Coincide con tu migración

    protected $fillable = [
        'nombre',
        'contacto',
        'telefono',
    ];

    /* ------------------ RELACIONES ------------------ */

    // Un proveedor puede tener muchas compras
    public function compras()
    {
        return $this->hasMany(Compra::class, 'proveedor_id');
    }
}
