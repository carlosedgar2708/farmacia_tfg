<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 🔹 Usuario Administrador
        $admin = User::create([
            'username' => 'admin',
            'name' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'admin@farmacia.com',
            'telefono' => '70012345',
            'password' => Hash::make('admin123'),
            'activo' => true,
        ]);

        $rolAdmin = Rol::where('slug', 'admin')->first();
        if ($rolAdmin) {
            $admin->rols()->attach($rolAdmin->id);
        }

        // 🔹 Usuario Vendedor
        $vendedor = User::create([
            'username' => 'vendedor',
            'name' => 'Carla',
            'apellido' => 'López',
            'email' => 'vendedor@farmacia.com',
            'telefono' => '70123456',
            'password' => Hash::make('vendedor123'),
            'activo' => true,
        ]);

        $rolVendedor = Rol::where('slug', 'vendedor')->first();
        if ($rolVendedor) {
            $vendedor->rols()->attach($rolVendedor->id);
        }

        // 🔹 Usuario Almacenero
        $almacenero = User::create([
            'username' => 'almacenero',
            'name' => 'Pedro',
            'apellido' => 'Gómez',
            'email' => 'almacenero@farmacia.com',
            'telefono' => '70234567',
            'password' => Hash::make('almacenero123'),
            'activo' => true,
        ]);

        $rolAlmacenero = Rol::where('slug', 'almacenero')->first();
        if ($rolAlmacenero) {
            $almacenero->rols()->attach($rolAlmacenero->id);
        }
    }
}
