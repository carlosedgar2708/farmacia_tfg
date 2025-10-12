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
        Schema::create('detalles_venta', function (Blueprint $table) {
        $table->id();
        $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
        $table->foreignId('lote_id')->constrained('lotes');
        $table->integer('cantidad');
        $table->decimal('precio_unitario', 12, 2);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_venta');
    }
};
