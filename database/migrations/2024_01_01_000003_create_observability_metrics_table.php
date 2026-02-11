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
        $tableName = config('observability.table_prefix', 'observability_') . 'metrics';

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('metric_type', 50); // response_time, memory_usage, error_rate, etc.
            $table->string('metric_name')->nullable(); // route name, controller action
            $table->string('dimension')->nullable(); // GET, POST, etc. or other categorization
            $table->decimal('value', 20, 4);
            $table->decimal('baseline', 20, 4)->nullable(); // For anomaly detection
            $table->decimal('z_score', 10, 4)->nullable(); // Statistical deviation
            $table->boolean('is_anomaly')->default(false);
            $table->string('aggregation_period', 20)->default('1h'); // 1h, 1d, 7d
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Database-agnostic indexes
            $table->index(['metric_type', 'metric_name']);
            $table->index(['created_at', 'metric_type']);
            $table->index('is_anomaly');
            $table->index(['period_start', 'period_end']);
            $table->index('aggregation_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('observability.table_prefix', 'observability_') . 'metrics';
        Schema::dropIfExists($tableName);
    }
};
