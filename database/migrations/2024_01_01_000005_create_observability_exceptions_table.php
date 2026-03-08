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
        $tableName = config('observability.table_prefix', 'observability_') . 'exceptions';

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('trace_id', 64)->nullable();
            $table->string('exception_class', 255);
            $table->text('message');
            $table->string('code', 50)->nullable();
            $table->string('file', 500);
            $table->unsignedInteger('line');
            $table->text('stack_trace')->nullable();
            $table->string('group_hash', 64);
            $table->unsignedInteger('occurrence_count')->default(1);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('context')->nullable();
            $table->string('severity', 20)->default('error');
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('group_hash');
            $table->index('exception_class');
            $table->index('trace_id');
            $table->index('created_at');
            $table->index('resolved');
            $table->index(['group_hash', 'resolved']);
            $table->index('last_seen_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('observability.table_prefix', 'observability_') . 'exceptions';
        Schema::dropIfExists($tableName);
    }
};
