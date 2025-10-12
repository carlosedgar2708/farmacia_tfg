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
        Schema::create('permisos', function (Blueprint $table) {
            //Los Atributos de mi tablas
            $table->id(); //Primary key , autoincremental, tipo biinteger
            $table->string('nombre')->unique();   // <-- agrega
            $table->string('slug')->unique();     // <-- agrega
            $table->string('descripcion')->nullable(); // <-- opcional
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};
