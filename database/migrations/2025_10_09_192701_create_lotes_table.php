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
        Schema::create('lotes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
        $table->string('nro_lote',100);
        $table->date('fecha_vencimiento')->nullable();
        $table->decimal('costo_unitario',12,2);
        $table->integer('stock')->default(0);
        $table->timestamps();
        $table->softDeletes();
        $table->unique(['producto_id','nro_lote']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};
