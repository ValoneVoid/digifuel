<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add columns to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('role')->default('driver')->after('email'); // driver, spv, fuelman, depthead
            $table->string('jabatan')->default('Driver')->after('role');
            $table->string('nrp')->nullable()->after('jabatan');
            $table->string('company')->default('PT UNITED TRACTORS TBK SITE SATUI')->after('nrp');
            $table->string('signature_url')->nullable()->after('company');
        });

        // Vehicles master table
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('unit_code')->unique();
            $table->string('police_no');
            $table->string('model')->nullable();
            $table->string('company')->default('PT UNITED TRACTORS TBK SITE SATUI');
            $table->timestamps();
        });

        // Areas master table
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Vendors master table
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Fuel Requests table
        Schema::create('fuel_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('driver_name');
            $table->dateTime('request_date');
            $table->string('unit_code');
            $table->string('police_no');
            $table->string('company')->default('PT UNITED TRACTORS TBK SITE SATUI');
            $table->string('hm_km')->default('79');
            $table->decimal('total_liters', 8, 2);
            $table->string('area');
            
            // Approvers
            $table->foreignId('spv_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('spv_name')->nullable();
            $table->foreignId('fuelman_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('fuelman_name')->nullable();
            $table->foreignId('depthead_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('depthead_name')->nullable();
            $table->string('vendor_name');

            // Tier approval statuses: 'menunggu', 'disetujui', 'ditolak', '-'
            $table->string('status_spv')->default('menunggu');
            $table->timestamp('spv_action_at')->nullable();
            $table->text('spv_note')->nullable();

            $table->string('status_fuelman')->default('-');
            $table->timestamp('fuelman_action_at')->nullable();
            $table->text('fuelman_note')->nullable();

            $table->string('status_depthead')->default('-');
            $table->timestamp('depthead_action_at')->nullable();
            $table->text('depthead_note')->nullable();

            // Overall status: 'menunggu', 'disetujui', 'ditolak'
            $table->string('overall_status')->default('menunggu');

            // Signatures at time of request / approvals
            $table->string('driver_signature')->nullable();
            $table->string('spv_signature')->nullable();
            $table->string('fuelman_signature')->nullable();
            $table->string('depthead_signature')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_requests');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('vehicles');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'role', 'jabatan', 'nrp', 'company', 'signature_url']);
        });
    }
};
