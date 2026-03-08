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

        Schema::table($tableName, function (Blueprint $table) {
            $table->json('explain_output')->nullable()->after('stack_trace');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('observability.table_prefix', 'observability_') . 'queries';

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn('explain_output');
        });
    }
};
