<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inversiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Una sola tabla con columna discriminadora, no dos tablas: el
            // numero de campos distintos entre ambos tipos es bajo.
            $table->enum('tipo', ['bien', 'financiera']);

            $table->string('nombre', 150);
            $table->decimal('monto_invertido', 12, 2);
            $table->date('fecha_inicio');

            // Solo para tipo = 'financiera'
            $table->string('instrumento', 100)->nullable();
            $table->decimal('valor_actual', 12, 2)->nullable();

            // Solo para tipo = 'bien'
            $table->string('clasificacion', 100)->nullable();
            $table->string('garantia', 100)->nullable();

            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'tipo'], 'inversiones_user_tipo_index');
        });

        DB::statement('ALTER TABLE inversiones ADD CONSTRAINT chk_inversiones_monto CHECK (monto_invertido > 0)');
        DB::statement('ALTER TABLE inversiones ADD CONSTRAINT chk_inversiones_valor CHECK (valor_actual IS NULL OR valor_actual >= 0)');

        // El precio de usar una sola tabla con columnas nullables es que nada
        // impide un 'bien' con instrumento financiero. Este CHECK sostiene la
        // coherencia que la estructura por si sola no expresa.
        DB::statement("ALTER TABLE inversiones ADD CONSTRAINT chk_inversiones_tipo CHECK (
            (tipo = 'financiera' AND clasificacion IS NULL AND garantia IS NULL)
            OR (tipo = 'bien' AND instrumento IS NULL AND valor_actual IS NULL)
        )");
    }

    public function down(): void
    {
        Schema::dropIfExists('inversiones');
    }
};
