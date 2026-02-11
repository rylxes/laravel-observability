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
        $tableName = config('observability.table_prefix', 'observability_') . 'alerts';

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('alert_type', 50); // slow_query, high_memory, error_spike, anomaly
            $table->string('severity', 20)->default('warning'); // info, warning, error, critical
            $table->string('title');
            $table->text('description');
            $table->string('source')->nullable(); // Route, query, etc.
            $table->json('context')->nullable(); // Additional data
            $table->string('fingerprint', 64)->nullable(); // For deduplication
            $table->boolean('notified')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->string('notification_channels')->nullable(); // slack,telegram
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            // Database-agnostic indexes
            $table->index(['alert_type', 'severity']);
            $table->index('created_at');
            $table->index('fingerprint');
            $table->index(['notified', 'notified_at']);
            $table->index(['resolved', 'resolved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('observability.table_prefix', 'observability_') . 'alerts';
        Schema::dropIfExists($tableName);
    }
};
