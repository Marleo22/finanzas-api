<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egresos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();

            // Opcional: no todo gasto necesita subcategoria.
            // Que la subcategoria pertenezca a la categoria elegida es una
            // regla que la FK no puede expresar; va en el Form Request.
            $table->foreignId('subcategoria_id')->nullable()->constrained('subcategorias')->nullOnDelete();

            $table->date('fecha');
            $table->string('descripcion', 150);
            $table->decimal('monto', 12, 2);
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'fecha'], 'egresos_user_fecha_index');
            $table->index(['user_id', 'categoria_id', 'fecha'], 'egresos_user_categoria_fecha_index');
        });

        DB::statement('ALTER TABLE egresos ADD CONSTRAINT chk_egresos_monto CHECK (monto > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('egresos');
    }
};
