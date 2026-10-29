<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add external_system to employees if missing
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (!Schema::hasColumn('employees', 'external_system')) {
                    $table->string('external_system')->nullable()->after('source');
                }
            });
        }

        // 2. Add snapshot & realization columns to fuel_requests if missing
        if (Schema::hasTable('fuel_requests')) {
            Schema::table('fuel_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('fuel_requests', 'driver_nrp')) {
                    $table->string('driver_nrp')->nullable()->after('driver_name');
                    $table->string('driver_position')->nullable()->after('driver_nrp');
                    $table->string('driver_department')->nullable()->after('driver_position');
                    $table->string('source_company')->nullable()->after('company');
                }
                if (!Schema::hasColumn('fuel_requests', 'unit_type')) {
                    $table->string('unit_type')->nullable()->after('police_no');
                }
                if (!Schema::hasColumn('fuel_requests', 'vendor_user_id')) {
                    $table->foreignId('vendor_user_id')->nullable()->after('vendor_id')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('fuel_requests', 'actual_liters')) {
                    $table->decimal('actual_liters', 8, 2)->nullable()->after('total_liters');
                    $table->decimal('actual_hm_km', 10, 2)->nullable()->after('actual_liters');
                    $table->dateTime('fueled_at')->nullable()->after('actual_hm_km');
                }
            });
        }

        // 3. Make fuel_request_id nullable in fuel_request_history for system-wide audit logs
        if (Schema::hasTable('fuel_request_history')) {
            Schema::table('fuel_request_history', function (Blueprint $table) {
                $table->unsignedBigInteger('fuel_request_id')->nullable()->change();
            });
        }

        // 4. Add extended evaluation columns to fuel_evaluations if missing
        if (Schema::hasTable('fuel_evaluations')) {
            Schema::table('fuel_evaluations', function (Blueprint $table) {
                if (!Schema::hasColumn('fuel_evaluations', 'actual_ratio')) {
                    $table->decimal('actual_ratio', 10, 2)->default(0)->after('total_standard_liters');
                }
                if (!Schema::hasColumn('fuel_evaluations', 'standard_type')) {
                    $table->string('standard_type')->default('km_per_liter')->after('actual_ratio');
                }
                if (!Schema::hasColumn('fuel_evaluations', 'tolerance_used')) {
                    $table->decimal('tolerance_used', 5, 2)->default(5.00)->after('standard_used');
                }
                if (!Schema::hasColumn('fuel_evaluations', 'total_actual_cost')) {
                    $table->decimal('total_actual_cost', 14, 2)->default(0)->after('price_used');
                }
                if (!Schema::hasColumn('fuel_evaluations', 'notes')) {
                    $table->text('notes')->nullable()->after('evaluation_status');
                }
            });
        }

        // 5. Create user_notifications table if missing
        if (!Schema::hasTable('user_notifications')) {
            Schema::create('user_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('fuel_request_id')->nullable()->constrained('fuel_requests')->cascadeOnDelete();
                $table->string('title');
                $table->text('message');
                $table->string('type')->default('info');
                $table->boolean('is_read')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};

