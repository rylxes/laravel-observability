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
        $tableName = config('observability.table_prefix', 'observability_') . 'traces';

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('trace_id', 64)->unique();
            $table->string('parent_trace_id', 64)->nullable();
            $table->string('route_name')->nullable();
            $table->string('route_action')->nullable();
            $table->string('method', 10);
            $table->text('url');
            $table->integer('status_code')->nullable();
            $table->unsignedBigInteger('duration_ms')->default(0);
            $table->unsignedBigInteger('memory_usage')->default(0);
            $table->unsignedInteger('query_count')->default(0);
            $table->unsignedBigInteger('query_time_ms')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('user_id')->nullable();
            $table->json('metadata')->nullable();
            $table->json('headers')->nullable();
            $table->json('request_payload')->nullable();
            $table->timestamps();

            // Database-agnostic indexes
            $table->index('trace_id');
            $table->index('parent_trace_id');
            $table->index(['created_at', 'duration_ms']);
            $table->index('route_name');
            $table->index('status_code');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('observability.table_prefix', 'observability_') . 'traces';
        Schema::dropIfExists($tableName);
    }
};
