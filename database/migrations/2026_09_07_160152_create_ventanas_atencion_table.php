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
        Schema::create('ventanas_atencion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 40); // ej. "Grupo A"
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->integer('capacidad_maxima')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventanas_atencion');
    }
};
