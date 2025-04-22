<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckTableStructureCommand extends Command
{
    protected $signature = 'db:check-table {table : Имя таблицы для проверки}';
    protected $description = 'Проверяет структуру указанной таблицы';

    public function handle()
    {
        $tableName = $this->argument('table');
        
        if (!Schema::hasTable($tableName)) {
            $this->error("Таблица '{$tableName}' не существует!");
            return 1;
        }
        
        $this->info("Структура таблицы '{$tableName}':");
        
        $columns = Schema::getColumnListing($tableName);
        
        $tableData = [];
        foreach ($columns as $column) {
            $type = DB::getSchemaBuilder()->getColumnType($tableName, $column);
            $tableData[] = [
                'column' => $column,
                'type' => $type,
                'nullable' => Schema::getConnection()->getDoctrineColumn($tableName, $column)->getNotnull() ? 'NO' : 'YES'
            ];
        }
        
        $this->table(['Колонка', 'Тип', 'Nullable'], $tableData);
        
        return 0;
    }
}
