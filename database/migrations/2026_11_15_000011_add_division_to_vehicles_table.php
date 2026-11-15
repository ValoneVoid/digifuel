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
        if (Schema::hasTable('vehicles') && !Schema::hasColumn('vehicles', 'division')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->string('division', 100)->nullable()->after('model');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('vehicles') && Schema::hasColumn('vehicles', 'division')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropColumn('division');
            });
        }
    }
};
