<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('admin.database.connection') ?: config('database.default');
        $tableName = config('admin.extensions.reporter.table', 'laravel_exceptions');
        $schema = Schema::connection($connection);

        if (!$schema->hasTable($tableName)) {
            return;
        }

        $schema->table($tableName, function (Blueprint $table) use ($schema, $tableName) {
            if (!$schema->hasIndex($tableName, ['type'])) {
                $table->index('type');
            }
            if (!$schema->hasIndex($tableName, ['method'])) {
                $table->index('method');
            }
            if (!$schema->hasIndex($tableName, ['ip'])) {
                $table->index('ip');
            }
            if (!$schema->hasIndex($tableName, ['created_at'])) {
                $table->index('created_at');
            }
        });
    }

    public function down(): void
    {
        $connection = config('admin.database.connection') ?: config('database.default');
        $tableName = config('admin.extensions.reporter.table', 'laravel_exceptions');
        $schema = Schema::connection($connection);

        if (!$schema->hasTable($tableName)) {
            return;
        }

        $schema->table($tableName, function (Blueprint $table) use ($schema, $tableName) {
            foreach (['type', 'method', 'ip', 'created_at'] as $col) {
                if ($schema->hasIndex($tableName, [$col])) {
                    $table->dropIndex([$col]);
                }
            }
        });
    }
};
