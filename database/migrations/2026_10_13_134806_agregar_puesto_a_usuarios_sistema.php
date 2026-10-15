<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios_sistema', function (Blueprint $table) {
            if (!Schema::hasColumn('usuarios_sistema', 'puesto')) {
                $table->enum('puesto', ['puerta', 'cocina'])->nullable()->after('rol');
            }
        });
    }

    public function down(): void
    {
        Schema::table('usuarios_sistema', function (Blueprint $table) {
            if (Schema::hasColumn('usuarios_sistema', 'puesto')) {
                $table->dropColumn('puesto');
            }
        });
    }
};