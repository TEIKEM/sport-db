<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('leagues', 'api_football_id')) {
            Schema::table('leagues', function (Blueprint $table) {
                $table->dropColumn('api_football_id');
            });
        }

        if (Schema::hasColumn('fixtures', 'api_football_id')) {
            Schema::table('fixtures', function (Blueprint $table) {
                $table->dropColumn('api_football_id');
            });
        }
    }

    public function down(): void
    {
        // Volontairement vide : ce nettoyage n'a pas de retour en arrière prévu.
    }
};
