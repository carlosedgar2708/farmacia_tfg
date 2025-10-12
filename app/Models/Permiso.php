<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    use HasFactory;

    protected $table = 'permisos';

    protected $fillable = [
        'nombre',         // Ej: "crear_venta", "editar_usuario"
        'slug',           // Ej: "ventas.crear", "usuarios.editar"
        'descripcion',    // Texto opcional explicando el permiso
    ];

    /* ------------------ RELACIONES ------------------ */

    // Un permiso pertenece a muchos roles (relación M:N)
    public function rols()
    {
        return $this->belongsToMany(Rol::class, 'permiso_rol', 'permiso_id', 'rol_id');
    }
}
