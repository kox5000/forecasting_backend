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
        Schema::table('forecasts', function (Blueprint $table) {
            if (!Schema::hasColumn('forecasts', 'status')) {
                $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            }

            if (!Schema::hasColumn('forecasts', 'input_data')) {
                $table->json('input_data')->nullable();
            }

            if (!Schema::hasColumn('forecasts', 'result')) {
                $table->json('result')->nullable();
            }

            if (!Schema::hasColumn('forecasts', 'started_at')) {
                $table->timestamp('started_at')->nullable();
            }

            if (!Schema::hasColumn('forecasts', 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }

            if (!Schema::hasColumn('forecasts', 'job_id')) {
                $table->string('job_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('forecasts', function (Blueprint $table) {
            $table->dropColumn(['status', 'input_data', 'result', 'started_at', 'completed_at', 'job_id']);
        });
    }
};
