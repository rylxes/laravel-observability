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
        $tableName = config('observability.table_prefix', 'observability_') . 'queries';

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('trace_id', 64);
            $table->text('sql');
            $table->json('bindings')->nullable();
            $table->unsignedBigInteger('duration_ms');
            $table->string('connection_name')->nullable();
            $table->text('stack_trace')->nullable();
            $table->boolean('is_slow')->default(false);
            $table->boolean('is_duplicate')->default(false);
            $table->string('query_type', 20)->nullable(); // SELECT, INSERT, UPDATE, DELETE
            $table->string('table_name')->nullable();
            $table->timestamps();

            // Database-agnostic indexes
            $table->index('trace_id');
            $table->index(['is_slow', 'duration_ms']);
            $table->index('created_at');
            $table->index('table_name');
            $table->index('query_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('observability.table_prefix', 'observability_') . 'queries';
        Schema::dropIfExists($tableName);
    }
};
