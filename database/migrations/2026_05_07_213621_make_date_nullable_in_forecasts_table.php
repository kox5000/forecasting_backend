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
            $table->date('date')->nullable()->change();
            $table->decimal('predicted_revenue', 10, 2)->nullable()->change();
            $table->string('model_version')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forecasts', function (Blueprint $table) {
            $table->date('date')->nullable(false)->change();
            $table->decimal('predicted_revenue', 10, 2)->nullable(false)->change();
            $table->string('model_version')->nullable(false)->change();
        });
    }
};
