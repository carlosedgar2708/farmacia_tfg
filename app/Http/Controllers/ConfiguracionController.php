<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    public function edit()
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $diasAlertaVencimiento = Configuracion::obtener('dias_alerta_vencimiento', 90);

        return view('configuracion.edit', compact('diasAlertaVencimiento'));
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $data = $request->validate([
            'dias_alerta_vencimiento' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        Configuracion::establecer('dias_alerta_vencimiento', $data['dias_alerta_vencimiento']);

        return redirect()->route('configuracion.edit')->with('success', 'Configuración actualizada correctamente.');
    }
}
