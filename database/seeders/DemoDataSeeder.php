<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\DetalleVenta;
use App\Models\Lote;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Recibo;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Datos de demostración para capturas de pantalla del Capítulo 4 (memoria de tesis).
 * No se engancha a DatabaseSeeder::run() a propósito: se ejecuta una sola vez a mano
 * (`php artisan db:seed --class=DemoDataSeeder`) para no duplicar datos en un
 * `migrate:fresh --seed` posterior. No toca `users`, `rols` ni `permisos`; usa los
 * usuarios admin@farmacia.com / vendedor@farmacia.com ya sembrados por UserSeeder.
 * La lógica de compras/ventas replica exactamente CompraController::store() y
 * VentaController::store() (mismos pasos: lote, detalle, stock, movimiento, recibo).
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@farmacia.com')->firstOrFail();
        $vendedor = User::where('email', 'vendedor@farmacia.com')->firstOrFail();

        DB::transaction(function () use ($admin, $vendedor) {
            $this->sembrarProveedores();
            $this->sembrarProductosNuevos();
            $this->sembrarClientes();
            $this->sembrarCompras($admin);
            $this->sembrarVentas($admin, $vendedor);
        });
    }

    private function sembrarProveedores(): void
    {
        $proveedores = [
            ['nombre' => 'Distribuidora Cruceñita', 'contacto' => 'Fernando Rojas', 'telefono' => '70045678'],
            ['nombre' => 'Laboratorios Bagó Bolivia', 'contacto' => 'Patricia Vargas', 'telefono' => '70156789'],
            ['nombre' => 'Droguería La Merced', 'contacto' => 'Marcelo Ibáñez', 'telefono' => '70267890'],
            ['nombre' => 'Import Salud S.R.L.', 'contacto' => 'Daniela Suárez', 'telefono' => '70378901'],
        ];

        foreach ($proveedores as $p) {
            Proveedor::firstOrCreate(['nombre' => $p['nombre']], $p);
        }
    }

    private function sembrarProductosNuevos(): void
    {
        $productos = [
            ['codigo' => 'P006', 'nombre' => 'Ibuprofeno 400mg tabletas', 'es_inyectable' => false, 'description' => 'Antiinflamatorio no esteroideo (AINE) de uso oral.', 'precio_venta' => 6.50],
            ['codigo' => 'P007', 'nombre' => 'Ácido Acetilsalicílico 100mg', 'es_inyectable' => false, 'description' => 'Antiagregante plaquetario y analgésico.', 'precio_venta' => 4.50],
            ['codigo' => 'P008', 'nombre' => 'Metformina 850mg tabletas', 'es_inyectable' => false, 'description' => 'Antidiabético oral, biguanida.', 'precio_venta' => 9.00],
            ['codigo' => 'P009', 'nombre' => 'Vitamina C 1g efervescente', 'es_inyectable' => false, 'description' => 'Suplemento vitamínico.', 'precio_venta' => 6.00],
            ['codigo' => 'P010', 'nombre' => 'Ranitidina 150mg tabletas', 'es_inyectable' => false, 'description' => 'Antagonista H2, reduce la acidez estomacal.', 'precio_venta' => 5.50],
        ];

        foreach ($productos as $p) {
            Producto::firstOrCreate(['codigo' => $p['codigo']], $p);
        }
    }

    private function sembrarClientes(): void
    {
        $clientes = [
            ['nombre' => 'Juana Pérez', 'documento' => '5551234', 'telefono' => '70111222'],
            ['nombre' => 'Carlos Vaca', 'documento' => '4448765', 'telefono' => '70222333'],
            ['nombre' => 'María Fernández', 'documento' => '6663322', 'telefono' => '70333444'],
            ['nombre' => 'Roberto Gutiérrez', 'documento' => '3339988', 'telefono' => '70444555'],
            ['nombre' => 'Lucía Rojas', 'documento' => '7772211', 'telefono' => '70555666'],
            ['nombre' => 'Diego Salvatierra', 'documento' => '8886655', 'telefono' => '70666777'],
        ];

        foreach ($clientes as $c) {
            Cliente::firstOrCreate(['nombre' => $c['nombre']], $c);
        }
    }

    private function sembrarCompras(User $admin): void
    {
        $comprasData = [
            [
                'dias_atras' => 55, 'proveedor' => 'Distribuidora Cruceñita',
                'items' => [
                    ['codigo' => 'P002', 'nro_lote' => 'LOT-2026-A', 'vence_meses' => 8, 'costo' => 3.50, 'cantidad' => 60],
                    ['codigo' => 'P001', 'nro_lote' => 'LOT-2026-P1', 'vence_meses' => 12, 'costo' => 1.80, 'cantidad' => 100],
                ],
            ],
            [
                'dias_atras' => 50, 'proveedor' => 'Laboratorios Bagó Bolivia',
                'items' => [
                    ['codigo' => 'P003', 'nro_lote' => 'LOT-2026-D1', 'vence_meses' => 10, 'costo' => 2.90, 'cantidad' => 40],
                    ['codigo' => 'P004', 'nro_lote' => 'LOT-2026-O1', 'vence_meses' => 14, 'costo' => 3.20, 'cantidad' => 70],
                ],
            ],
            [
                'dias_atras' => 45, 'proveedor' => 'Droguería La Merced',
                'items' => [
                    ['codigo' => 'P005', 'nro_lote' => 'LOT-2026-L1', 'vence_meses' => 9, 'costo' => 2.10, 'cantidad' => 55],
                    ['codigo' => 'P006', 'nro_lote' => 'LOT-2026-I1', 'vence_meses' => 11, 'costo' => 2.40, 'cantidad' => 80],
                ],
            ],
            [
                'dias_atras' => 38, 'proveedor' => 'Import Salud S.R.L.',
                'items' => [
                    ['codigo' => 'P007', 'nro_lote' => 'LOT-2026-AS1', 'vence_meses' => 7, 'costo' => 1.50, 'cantidad' => 90],
                    ['codigo' => 'P008', 'nro_lote' => 'LOT-2026-M1', 'vence_meses' => 13, 'costo' => 3.60, 'cantidad' => 65],
                ],
            ],
            [
                'dias_atras' => 30, 'proveedor' => 'Distribuidora Cruceñita',
                'items' => [
                    ['codigo' => 'P009', 'nro_lote' => 'LOT-2026-V1', 'vence_meses' => 6, 'costo' => 2.00, 'cantidad' => 75],
                    ['codigo' => 'P010', 'nro_lote' => 'LOT-2026-R1', 'vence_meses' => 10, 'costo' => 2.30, 'cantidad' => 60],
                    ['codigo' => 'P002', 'nro_lote' => 'LOT-2026-B', 'vence_meses' => 3, 'costo' => 3.70, 'cantidad' => 30],
                ],
            ],
            [
                'dias_atras' => 20, 'proveedor' => 'Laboratorios Bagó Bolivia',
                'items' => [
                    ['codigo' => 'P001', 'nro_lote' => 'LOT-2026-P2', 'vence_meses' => 5, 'costo' => 1.90, 'cantidad' => 50],
                    ['codigo' => 'P003', 'nro_lote' => 'LOT-2026-D2', 'vence_meses' => 4, 'costo' => 3.00, 'cantidad' => 35],
                ],
            ],
            [
                'dias_atras' => 10, 'proveedor' => 'Droguería La Merced',
                'items' => [
                    ['codigo' => 'P004', 'nro_lote' => 'LOT-2026-O2', 'vence_meses' => 16, 'costo' => 3.30, 'cantidad' => 40],
                    ['codigo' => 'P006', 'nro_lote' => 'LOT-2026-I2', 'vence_meses' => 12, 'costo' => 2.50, 'cantidad' => 50],
                ],
            ],
        ];

        foreach ($comprasData as $c) {
            $proveedor = Proveedor::where('nombre', $c['proveedor'])->firstOrFail();
            $fecha = now()->subDays($c['dias_atras']);

            $compra = Compra::create([
                'fecha'        => $fecha,
                'proveedor_id' => $proveedor->id,
                'user_id'      => $admin->id,
            ]);

            foreach ($c['items'] as $it) {
                $producto = Producto::where('codigo', $it['codigo'])->firstOrFail();

                $lote = Lote::where('producto_id', $producto->id)
                    ->where('nro_lote', $it['nro_lote'])
                    ->first();

                if (!$lote) {
                    $lote = Lote::create([
                        'producto_id'       => $producto->id,
                        'nro_lote'          => $it['nro_lote'],
                        'fecha_vencimiento' => now()->addMonths($it['vence_meses']),
                        'costo_unitario'    => $it['costo'],
                        'stock'             => 0,
                    ]);
                } else {
                    $lote->fecha_vencimiento = now()->addMonths($it['vence_meses']);
                    $lote->costo_unitario = $it['costo'];
                    $lote->save();
                }

                DetalleCompra::create([
                    'compra_id'      => $compra->id,
                    'lote_id'        => $lote->id,
                    'cantidad'       => $it['cantidad'],
                    'costo_unitario' => $it['costo'],
                ]);

                $lote->increment('stock', $it['cantidad']);

                MovimientoStock::create([
                    'lote_id'    => $lote->id,
                    'fecha'      => $fecha,
                    'tipo'       => 'Entrada',
                    'motivo'     => 'Compra',
                    'cantidad'   => $it['cantidad'],
                    'referencia' => 'Compra #'.$compra->id,
                ]);
            }
        }
    }

    private function sembrarVentas(User $admin, User $vendedor): void
    {
        $ventasData = [
            ['dias_atras' => 25, 'cliente' => 'Juana Pérez', 'user' => 'vendedor', 'observacion' => 'Venta mostrador',
                'items' => [['codigo' => 'P002', 'cantidad' => 8, 'descuento' => 0]]],
            ['dias_atras' => 24, 'cliente' => null, 'user' => 'vendedor', 'observacion' => null,
                'items' => [['codigo' => 'P001', 'cantidad' => 10, 'descuento' => 0]]],
            ['dias_atras' => 22, 'cliente' => 'Carlos Vaca', 'user' => 'admin', 'observacion' => null,
                'items' => [['codigo' => 'P003', 'cantidad' => 4, 'descuento' => 2.00]]],
            ['dias_atras' => 20, 'cliente' => null, 'user' => 'vendedor', 'observacion' => null,
                'items' => [['codigo' => 'P006', 'cantidad' => 12, 'descuento' => 0]]],
            ['dias_atras' => 18, 'cliente' => 'María Fernández', 'user' => 'vendedor', 'observacion' => null,
                'items' => [
                    ['codigo' => 'P004', 'cantidad' => 6, 'descuento' => 0],
                    ['codigo' => 'P005', 'cantidad' => 5, 'descuento' => 0],
                ]],
            ['dias_atras' => 15, 'cliente' => 'Roberto Gutiérrez', 'user' => 'admin', 'observacion' => null,
                'items' => [['codigo' => 'P007', 'cantidad' => 20, 'descuento' => 1.50]]],
            ['dias_atras' => 12, 'cliente' => null, 'user' => 'vendedor', 'observacion' => null,
                'items' => [['codigo' => 'P008', 'cantidad' => 7, 'descuento' => 0]]],
            ['dias_atras' => 9, 'cliente' => 'Lucía Rojas', 'user' => 'vendedor', 'observacion' => null,
                'items' => [['codigo' => 'P009', 'cantidad' => 15, 'descuento' => 0]]],
            ['dias_atras' => 6, 'cliente' => 'Diego Salvatierra', 'user' => 'admin', 'observacion' => null,
                'items' => [['codigo' => 'P010', 'cantidad' => 9, 'descuento' => 1.00]]],
            ['dias_atras' => 2, 'cliente' => 'Juana Pérez', 'user' => 'vendedor', 'observacion' => null,
                'items' => [
                    ['codigo' => 'P002', 'cantidad' => 6, 'descuento' => 0],
                    ['codigo' => 'P001', 'cantidad' => 8, 'descuento' => 0],
                ]],
        ];

        foreach ($ventasData as $v) {
            $cliente = $v['cliente'] ? Cliente::where('nombre', $v['cliente'])->first() : null;
            $usuario = $v['user'] === 'admin' ? $admin : $vendedor;
            $fecha = now()->subDays($v['dias_atras']);

            $venta = Venta::create([
                'cliente_id'  => $cliente?->id,
                'user_id'     => $usuario->id,
                'fecha_venta' => $fecha,
                'observacion' => $v['observacion'],
                'estado'      => 'confirmada',
            ]);

            $total = 0;

            foreach ($v['items'] as $it) {
                $producto = Producto::where('codigo', $it['codigo'])->firstOrFail();
                $precioUnitario = (float) $producto->precio_venta;

                $lotes = Lote::where('producto_id', $producto->id)
                    ->where('stock', '>', 0)
                    ->vigentes()
                    ->orderByRaw('fecha_vencimiento IS NULL, fecha_vencimiento ASC')
                    ->get();

                $stockTotal = $lotes->sum('stock');
                $cantidadSolicitada = min($it['cantidad'], $stockTotal);
                if ($cantidadSolicitada <= 0) {
                    continue;
                }

                $descuento = $it['descuento'];
                $total += ($cantidadSolicitada * $precioUnitario) - $descuento;

                $cantidadRestante = $cantidadSolicitada;

                foreach ($lotes as $lote) {
                    if ($cantidadRestante <= 0) {
                        break;
                    }

                    $tomar = min($cantidadRestante, (int) $lote->stock);

                    DetalleVenta::create([
                        'venta_id'        => $venta->id,
                        'producto_id'     => $producto->id,
                        'lote_id'         => $lote->id,
                        'cantidad'        => $tomar,
                        'precio_unitario' => $precioUnitario,
                    ]);

                    $lote->decrement('stock', $tomar);

                    MovimientoStock::create([
                        'lote_id'    => $lote->id,
                        'fecha'      => $fecha,
                        'tipo'       => 'Salida',
                        'motivo'     => 'Venta',
                        'cantidad'   => $tomar,
                        'referencia' => 'Venta #'.$venta->id,
                    ]);

                    $cantidadRestante -= $tomar;
                }
            }

            Recibo::create([
                'venta_id' => $venta->id,
                'monto'    => $total,
            ]);
        }
    }
}
