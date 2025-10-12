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
        Schema::create('movimientos_stock', function (Blueprint $table) {
        $table->id();
        $table->foreignId('lote_id')->constrained('lotes');
        $table->dateTime('fecha');
        $table->enum('tipo',['Entrada','Salida']);
        $table->enum('motivo',['Compra','Venta','Devolucion','Ajuste']);
        $table->integer('cantidad');
        $table->string('referencia')->nullable(); // id o código externo
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_stock');
    }
};
