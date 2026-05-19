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
        Schema::table('recommendations', function (Blueprint $table) {
            if (!Schema::hasColumn('recommendations', 'status')) {
                $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            }

            if (!Schema::hasColumn('recommendations', 'input_data')) {
                $table->json('input_data')->nullable();
            }

            if (!Schema::hasColumn('recommendations', 'result')) {
                $table->json('result')->nullable();
            }

            if (!Schema::hasColumn('recommendations', 'model_version')) {
                $table->string('model_version')->nullable();
            }

            if (!Schema::hasColumn('recommendations', 'started_at')) {
                $table->timestamp('started_at')->nullable();
            }

            if (!Schema::hasColumn('recommendations', 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }

            if (!Schema::hasColumn('recommendations', 'job_id')) {
                $table->string('job_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('recommendations', function (Blueprint $table) {
            $table->dropColumn(['status', 'input_data', 'result', 'model_version', 'started_at', 'completed_at', 'job_id']);
        });
    }
};
