<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();

            // Fecha de la venta
            $table->dateTime('fecha_venta');

            // Usuario que registró la venta
            $table->foreignId('user_id')->constrained('users');

            // Cliente (opcional)
            $table->foreignId('cliente_id')->nullable()->constrained('clientes');

            // Observaciones opcionales
            $table->string('observacion')->nullable();

            // Estado de la venta (borrador, confirmada, anulada, etc.)
            $table->string('estado')->default('confirmada');

            $table->timestamps();

            // ✅ Para SoftDeletes (borrado lógico)
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
