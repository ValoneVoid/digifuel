<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('system_backgrounds') && !Schema::hasColumn('system_backgrounds', 'overlay_opacity')) {
            Schema::table('system_backgrounds', function (Blueprint $table) {
                $table->decimal('overlay_opacity', 3, 2)->default(0.65)->after('notes');
            });

            DB::table('system_backgrounds')->update(['overlay_opacity' => 0.65]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('system_backgrounds') && Schema::hasColumn('system_backgrounds', 'overlay_opacity')) {
            Schema::table('system_backgrounds', function (Blueprint $table) {
                $table->dropColumn('overlay_opacity');
            });
        }
    }
};

