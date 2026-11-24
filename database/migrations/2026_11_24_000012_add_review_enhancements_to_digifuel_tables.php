<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. SPBU & User Final Verification columns on fuel_requests
        Schema::table('fuel_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('fuel_requests', 'is_spbu')) {
                $table->boolean('is_spbu')->default(false)->after('vendor_name');
            }
            if (!Schema::hasColumn('fuel_requests', 'spbu_location')) {
                $table->string('spbu_location', 255)->nullable()->after('is_spbu');
            }
            if (!Schema::hasColumn('fuel_requests', 'spbu_price_per_liter')) {
                $table->decimal('spbu_price_per_liter', 12, 2)->nullable()->after('spbu_location');
            }
            if (!Schema::hasColumn('fuel_requests', 'spbu_receipt_photo')) {
                $table->string('spbu_receipt_photo', 255)->nullable()->after('spbu_price_per_liter');
            }
            if (!Schema::hasColumn('fuel_requests', 'user_verified_at')) {
                $table->timestamp('user_verified_at')->nullable()->after('fueled_at');
            }
            if (!Schema::hasColumn('fuel_requests', 'user_verification_note')) {
                $table->text('user_verification_note')->nullable()->after('user_verified_at');
            }
        });

        // 2. Standard Ratio Range (Max) on fuel_standards
        Schema::table('fuel_standards', function (Blueprint $table) {
            if (!Schema::hasColumn('fuel_standards', 'standard_max')) {
                $table->decimal('standard_max', 8, 2)->nullable()->after('standard_value');
            }
        });

        // 3. Cumulative Evaluation & Custom Admin Notes on fuel_evaluations
        Schema::table('fuel_evaluations', function (Blueprint $table) {
            if (!Schema::hasColumn('fuel_evaluations', 'standard_max_used')) {
                $table->decimal('standard_max_used', 8, 2)->nullable()->after('standard_used');
            }
            if (!Schema::hasColumn('fuel_evaluations', 'cumulative_meter_start')) {
                $table->decimal('cumulative_meter_start', 12, 2)->nullable()->after('evaluation_status');
            }
            if (!Schema::hasColumn('fuel_evaluations', 'cumulative_meter_end')) {
                $table->decimal('cumulative_meter_end', 12, 2)->nullable()->after('cumulative_meter_start');
            }
            if (!Schema::hasColumn('fuel_evaluations', 'cumulative_distance')) {
                $table->decimal('cumulative_distance', 12, 2)->default(0)->after('cumulative_meter_end');
            }
            if (!Schema::hasColumn('fuel_evaluations', 'cumulative_liters')) {
                $table->decimal('cumulative_liters', 12, 2)->default(0)->after('cumulative_distance');
            }
            if (!Schema::hasColumn('fuel_evaluations', 'cumulative_ratio')) {
                $table->decimal('cumulative_ratio', 10, 2)->default(0)->after('cumulative_liters');
            }
            if (!Schema::hasColumn('fuel_evaluations', 'cumulative_status')) {
                $table->string('cumulative_status', 30)->nullable()->after('cumulative_ratio');
            }
            if (!Schema::hasColumn('fuel_evaluations', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fuel_evaluations', function (Blueprint $table) {
            $cols = array_filter([
                'standard_max_used',
                'cumulative_meter_start',
                'cumulative_meter_end',
                'cumulative_distance',
                'cumulative_liters',
                'cumulative_ratio',
                'cumulative_status',
                'admin_notes',
            ], fn ($c) => Schema::hasColumn('fuel_evaluations', $c));

            if (!empty($cols)) {
                $table->dropColumn(array_values($cols));
            }
        });

        Schema::table('fuel_standards', function (Blueprint $table) {
            if (Schema::hasColumn('fuel_standards', 'standard_max')) {
                $table->dropColumn('standard_max');
            }
        });

        Schema::table('fuel_requests', function (Blueprint $table) {
            $cols = array_filter([
                'is_spbu',
                'spbu_location',
                'spbu_price_per_liter',
                'spbu_receipt_photo',
                'user_verified_at',
                'user_verification_note',
            ], fn ($c) => Schema::hasColumn('fuel_requests', $c));

            if (!empty($cols)) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
