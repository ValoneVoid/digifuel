<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('system_backgrounds')) {
            Schema::create('system_backgrounds', function (Blueprint $table) {
                $table->id();
                $table->string('title', 150);
                $table->string('image_path', 255);
                $table->string('notes', 255)->nullable();
                $table->boolean('is_active')->default(false);
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();
            });

            DB::table('system_backgrounds')->insert([
                'title' => 'Background Default Operasional Site Satui',
                'image_path' => 'images/mining_bg.jpg',
                'notes' => 'Background standar alat berat PT United Tractors Tbk - Site Satui',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_backgrounds');
    }
};

