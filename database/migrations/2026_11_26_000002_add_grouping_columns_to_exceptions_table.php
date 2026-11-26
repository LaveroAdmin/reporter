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
            if (!$schema->hasColumn($tableName, 'fingerprint')) {
                $table->string('fingerprint', 64)->nullable()->index()->after('type');
            }
            if (!$schema->hasColumn($tableName, 'occurrences')) {
                $table->unsignedInteger('occurrences')->default(1)->after('line');
            }
            if (!$schema->hasColumn($tableName, 'status')) {
                $table->string('status', 20)->default('open')->index()->after('occurrences');
            }
            if (!$schema->hasColumn($tableName, 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->index()->after('updated_at');
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

        $drop = [];
        foreach (['fingerprint', 'occurrences', 'status', 'last_seen_at'] as $col) {
            if ($schema->hasColumn($tableName, $col)) {
                $drop[] = $col;
            }
        }

        if (!empty($drop)) {
            $schema->table($tableName, function (Blueprint $table) use ($drop) {
                $table->dropColumn($drop);
            });
        }
    }
};
