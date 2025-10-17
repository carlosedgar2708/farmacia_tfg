<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Rol;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $adminRole    = Rol::where('slug', 'admin')->first();
            $vendedorRole = Rol::where('slug', 'vendedor')->first();

            // Admin
            $admin = User::updateOrCreate(
                ['email' => 'admin@farmacia.com'],
                [
                    'username' => 'admin',        // <= OBLIGATORIO
                    'name'     => 'Juan',
                    'apellido' => 'Admin',
                    'telefono' => null,
                    'password' => Hash::make('admin123'),
                    'activo'   => true,
                ]
            );
            if ($adminRole) {
                $admin->rols()->syncWithoutDetaching([$adminRole->id]);
            }

            // Vendedor
            $vend = User::updateOrCreate(
                ['email' => 'vendedor@farmacia.com'],
                [
                    'username' => 'vendedor',     // <= OBLIGATORIO
                    'name'     => 'Carla',
                    'apellido' => 'Vendedora',
                    'telefono' => null,
                    'password' => Hash::make('vendedor123'),
                    'activo'   => true,
                ]
            );
            if ($vendedorRole) {
                $vend->rols()->syncWithoutDetaching([$vendedorRole->id]);
            }
        });
    }
}
