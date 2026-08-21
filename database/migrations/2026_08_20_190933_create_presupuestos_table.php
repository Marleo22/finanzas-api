<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();

            // El esquema de referencia usaba anio + mes. Se sustituyen por una
            // sola fecha anclada al dia 1, para no romper la regla de que
            // ninguna tabla lleva columna mes: el periodo se compara por rango
            // igual que en ingresos y egresos, sin YEAR()/MONTH().
            $table->date('periodo');

            $table->decimal('monto_planeado', 12, 2);
            $table->timestamps();

            // Un presupuesto por categoria y periodo.
            $table->unique(['user_id', 'categoria_id', 'periodo'], 'presupuestos_periodo_unique');
            $table->index('categoria_id', 'presupuestos_categoria_id_index');
        });

        // Impide guardar 2026-08-15 y 2026-08-01 como periodos distintos del
        // mismo mes, que reventaria el indice unico sin que se note.
        DB::statement('ALTER TABLE presupuestos ADD CONSTRAINT chk_presupuestos_periodo CHECK (DAYOFMONTH(periodo) = 1)');
        DB::statement('ALTER TABLE presupuestos ADD CONSTRAINT chk_presupuestos_monto CHECK (monto_planeado >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuestos');
    }
};
