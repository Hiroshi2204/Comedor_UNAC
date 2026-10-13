<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tickets MODIFY estado
            ENUM('generado','validado','cancelado','recogido','no_recogido','vencido') NOT NULL DEFAULT 'generado'");

        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'validado_at')) {
                $table->dateTime('validado_at')->nullable()->after('fecha_hora_recojo');
            }
            if (!Schema::hasColumn('tickets', 'validado_por')) {
                $table->foreignId('validado_por')->nullable()->after('validado_at')
                    ->constrained('usuarios_sistema')->nullOnDelete();
            }
        });

        if (!Schema::hasTable('escaneos_ticket')) {
            Schema::create('escaneos_ticket', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
                $table->string('codigo_qr', 64);
                $table->enum('puesto', ['puerta', 'entrega']);
                $table->foreignId('trabajador_id')->nullable()->constrained('usuarios_sistema')->nullOnDelete();
                $table->boolean('exitoso');
                $table->string('resultado', 30);
                $table->timestamp('created_at')->useCurrent();

                $table->index(['ticket_id', 'puesto']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('escaneos_ticket');

        DB::table('tickets')->where('estado', 'validado')->update(['estado' => 'generado']);
        DB::statement("ALTER TABLE tickets MODIFY estado
            ENUM('generado','cancelado','recogido','no_recogido','vencido') NOT NULL DEFAULT 'generado'");

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validado_por');
            $table->dropColumn('validado_at');
        });
    }
};