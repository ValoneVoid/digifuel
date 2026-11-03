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
        // 1. Create unit_vendors table
        if (!Schema::hasTable('unit_vendors')) {
            Schema::create('unit_vendors', function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable()->unique();
                $table->string('name')->unique();
                $table->string('contact_person')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Add unit_vendor_id to vehicles
        if (Schema::hasTable('vehicles') && !Schema::hasColumn('vehicles', 'unit_vendor_id')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->foreignId('unit_vendor_id')->nullable()->after('model')->constrained('unit_vendors')->nullOnDelete();
            });
        }

        // 3. Add unit_vendor_id to fuel_requests
        if (Schema::hasTable('fuel_requests') && !Schema::hasColumn('fuel_requests', 'unit_vendor_id')) {
            Schema::table('fuel_requests', function (Blueprint $table) {
                $table->foreignId('unit_vendor_id')->nullable()->after('vendor_id')->constrained('unit_vendors')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('fuel_requests') && Schema::hasColumn('fuel_requests', 'unit_vendor_id')) {
            Schema::table('fuel_requests', function (Blueprint $table) {
                $table->dropConstrainedForeignId('unit_vendor_id');
            });
        }

        if (Schema::hasTable('vehicles') && Schema::hasColumn('vehicles', 'unit_vendor_id')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropConstrainedForeignId('unit_vendor_id');
            });
        }

        Schema::dropIfExists('unit_vendors');
    }
};

