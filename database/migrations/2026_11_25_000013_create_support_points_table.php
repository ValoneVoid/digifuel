<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add support_point_id to fuel_requests (nullable)
        Schema::table('fuel_requests', function (Blueprint $table) {
            $table->foreignId('support_point_id')
                ->nullable()
                ->after('area_id')
                ->constrained('support_points')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fuel_requests', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\SupportPoint::class);
            $table->dropColumn('support_point_id');
        });

        Schema::dropIfExists('support_points');
    }
};
