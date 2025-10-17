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
    public function rols() // <- usamos "rols" como acordamos
    {
        // pivote: rol_user (user_id, rol_id)
        return $this->belongsToMany(\App\Models\Rol::class, 'rol_user', 'user_id', 'rol_id');
    }
    // Permisos a través de roles
    public function permisos()
    {
    return \App\Models\Permiso::query()
        ->whereIn('id', function ($q) {
            $q->select('permiso_id')
              ->from('permiso_rol')
              ->whereIn('rol_id', $this->rols()->pluck('rols.id'));
        })->get();
    }
    public function esAdmin(): bool
    {
        // por nombre o slug según tu data
        return $this->rols()
            ->where('nombre', 'Administrador')
            ->orWhere('slug', 'admin')
            ->exists();
    }

    public function tienePermiso(string $slug): bool
    {
        return $this->rols()->whereHas('permisos', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->exists();
    }
}
