<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'username',
        'name',
        'apellido',
        'email',
        'telefono',
        'password',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // 👇 Agrega esta relación con roles
    public function rols()
    {
        return $this->belongsToMany(Rol::class, 'rol_user', 'user_id', 'rol_id');
    }

    // Permisos a través de roles
    public function permisos()
    {
        return $this->hasManyThrough(
            Permiso::class,
            Rol::class,
            'id',          // clave local en Rol
            'id',          // clave local en Permiso
            'id',          // clave local en User
            'rol_id'       // FK pivote rol_user
        );
    }


    public function tienePermiso(string $slug): bool
    {
        return $this->rols()->whereHas('permisos', fn($q) => $q->where('slug', $slug))->exists();
    }
}
