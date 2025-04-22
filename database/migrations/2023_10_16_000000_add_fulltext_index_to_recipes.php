<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Добавляем FULLTEXT индекс к таблице recipes
        DB::statement('ALTER TABLE recipes ADD FULLTEXT search_index (title, description, ingredients)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем FULLTEXT индекс
        DB::statement('ALTER TABLE recipes DROP INDEX search_index');
    }
};
