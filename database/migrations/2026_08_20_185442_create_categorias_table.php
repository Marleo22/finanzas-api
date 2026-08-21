<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();

            // NULL = categoria del sistema, visible para todos.
            // Con valor = categoria de ese usuario, solo el la ve.
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();

            $table->string('nombre', 80);
            $table->enum('tipo', ['ingreso', 'egreso']);
            $table->timestamps();

            // UNIQUE (user_id, nombre, tipo) no protege a las categorias del
            // sistema: en MySQL dos NULL no colisionan en un indice unico, asi
            // que 'Vivienda' con user_id NULL se puede insertar dos veces.
            // Esta columna generada (VIRTUAL: STORED choca con el ON DELETE
            // CASCADE de user_id) colapsa NULL a 0 y hace que el indice si
            // aplique a las globales. Los id de users empiezan en 1, asi que 0
            // nunca choca con un usuario real.
            $table->unsignedBigInteger('user_key')->virtualAs('COALESCE(user_id, 0)');
            $table->unique(['user_key', 'nombre', 'tipo'], 'categorias_user_nombre_tipo_unique');

            $table->index('user_id', 'categorias_user_id_index');
            $table->index('tipo', 'categorias_tipo_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
