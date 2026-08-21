<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcategorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();

            // Sin esta columna, una subcategoria creada por un usuario bajo una
            // categoria del sistema quedaria visible para todos los usuarios.
            // NULL = subcategoria del sistema; con valor = de ese usuario.
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();

            $table->string('nombre', 80);
            $table->timestamps();

            $table->unique(['categoria_id', 'nombre'], 'subcategorias_categoria_nombre_unique');
            $table->index('user_id', 'subcategorias_user_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcategorias');
    }
};
