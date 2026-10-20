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
        Schema::create('usuarios_sistema', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('email', 150)->unique()->nullable();
            $table->string('username', 100)->unique();
            $table->string('password');
            $table->enum('rol', ['administrador', 'trabajador']);
            $table->enum('puesto', ['puerta', 'cocina'])->nullable(); // solo para trabajadores
            $table->boolean('activo')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios_sistema');
    }
};
