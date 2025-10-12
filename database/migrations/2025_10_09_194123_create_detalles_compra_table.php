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
        Schema::create('detalles_compra', function (Blueprint $table) {
        $table->id();
        $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
        $table->foreignId('lote_id')->constrained('lotes');
        $table->integer('cantidad');
        $table->decimal('costo_unitario', 12, 2);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_compra');
    }
};
