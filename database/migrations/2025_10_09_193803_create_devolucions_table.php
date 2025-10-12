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
        Schema::create('devolucions', function (Blueprint $table) {
        $table->id();
        $table->dateTime('fecha');
        $table->foreignId('user_id')->constrained('users'); // quién la registró
        $table->text('motivo')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devolucions');
    }
};
