<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->unsignedInteger('api_football_id')->nullable()->unique();
        });

        Schema::table('fixtures', function (Blueprint $table) {
            $table->unsignedBigInteger('api_football_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->dropColumn('api_football_id');
        });

        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn('api_football_id');
        });
    }
};
