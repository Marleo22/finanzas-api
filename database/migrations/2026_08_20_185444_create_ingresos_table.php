<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingresos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();

            // No existe columna mes: se deriva de fecha con rangos.
            $table->date('fecha');

            $table->string('fuente', 150);
            $table->decimal('monto', 12, 2);
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'fecha'], 'ingresos_user_fecha_index');

            // Totales por categoria dentro de un rango, sin leer todo el mes.
            $table->index(['user_id', 'categoria_id', 'fecha'], 'ingresos_user_categoria_fecha_index');
        });

        // > 0 y no >= 0: un movimiento de 0.00 no representa nada.
        DB::statement('ALTER TABLE ingresos ADD CONSTRAINT chk_ingresos_monto CHECK (monto > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ingresos');
    }
};
