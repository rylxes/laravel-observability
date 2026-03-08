<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = config('observability.table_prefix', 'observability_') . 'deployments';

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('version', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('commit_hash', 40)->nullable();
            $table->string('branch', 100)->nullable();
            $table->string('deployer', 100)->nullable();
            $table->string('environment', 50);
            $table->json('metadata')->nullable();
            $table->timestamp('deployed_at');
            $table->timestamps();

            $table->index('deployed_at');
            $table->index('version');
            $table->index('environment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('observability.table_prefix', 'observability_') . 'deployments';

        Schema::dropIfExists($tableName);
    }
};
