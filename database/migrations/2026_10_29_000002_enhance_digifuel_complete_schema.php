<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Roles table
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique(); // admin, employee, spv, manager, vendor, fuelman
                $table->string('display_name');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        // 2. User Roles pivot table
        if (!Schema::hasTable('user_roles')) {
            Schema::create('user_roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'role_id']);
            });
        }

        // 3. Employees master table (prepared for DIGISIM integration)
        if (!Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->string('nrp')->unique();
                $table->string('name');
                $table->string('position')->nullable(); // Jabatan
                $table->string('department')->nullable();
                $table->string('company')->default('PT UNITED TRACTORS Tbk - SITE SATUI');
                $table->string('source')->default('local'); // local, digisim
                $table->string('external_system')->nullable(); // e.g. DIGISIM
                $table->string('external_reference')->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('employees', function (Blueprint $table) {
                if (!Schema::hasColumn('employees', 'external_system')) {
                    $table->string('external_system')->nullable()->after('source');
                }
            });
        }

        // 4. Enhance Users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'employee_id')) {
                $table->foreignId('employee_id')->nullable()->after('id')->constrained('employees')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'vendor_id')) {
                $table->foreignId('vendor_id')->nullable()->after('employee_id')->constrained('vendors')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('signature_url');
            }
        });

        // 5. Enhance Vehicles (Units) table
        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'unit_type')) {
                $table->string('unit_type')->default('Light Vehicle')->after('model'); // Light Vehicle, Heavy Equipment, Dump Truck, etc.
            }
            if (!Schema::hasColumn('vehicles', 'meter_type')) {
                $table->enum('meter_type', ['km', 'hm'])->default('km')->after('unit_type');
            }
            if (!Schema::hasColumn('vehicles', 'default_area_id')) {
                $table->foreignId('default_area_id')->nullable()->after('meter_type')->constrained('areas')->nullOnDelete();
            }
            if (!Schema::hasColumn('vehicles', 'tank_capacity')) {
                $table->decimal('tank_capacity', 8, 2)->nullable()->after('default_area_id');
            }
            if (!Schema::hasColumn('vehicles', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('tank_capacity');
            }
            if (!Schema::hasColumn('vehicles', 'notes')) {
                $table->text('notes')->nullable()->after('is_active');
            }
        });

        // 6. Enhance Areas table
        Schema::table('areas', function (Blueprint $table) {
            if (!Schema::hasColumn('areas', 'code')) {
                $table->string('code')->nullable()->after('id');
            }
            if (!Schema::hasColumn('areas', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('name');
            }
        });

        // 7. Enhance Vendors table
        Schema::table('vendors', function (Blueprint $table) {
            if (!Schema::hasColumn('vendors', 'code')) {
                $table->string('code')->nullable()->after('id');
            }
            if (!Schema::hasColumn('vendors', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('name');
            }
            if (!Schema::hasColumn('vendors', 'phone')) {
                $table->string('phone')->nullable()->after('contact_person');
            }
            if (!Schema::hasColumn('vendors', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('phone');
            }
        });

        // 8. Fuel Standards table (KM/L atau Liter/Jam)
        if (!Schema::hasTable('fuel_standards')) {
            Schema::create('fuel_standards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
                $table->enum('standard_type', ['km_per_liter', 'liter_per_hour'])->default('km_per_liter');
                $table->decimal('standard_value', 8, 2); // e.g. 5.50 KM/L or 45.00 L/hour
                $table->decimal('tolerance_percentage', 5, 2)->default(5.00); // 5% tolerance
                $table->date('effective_start');
                $table->date('effective_end')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 9. Fuel Prices table
        if (!Schema::hasTable('fuel_prices')) {
            Schema::create('fuel_prices', function (Blueprint $table) {
                $table->id();
                $table->string('fuel_type')->default('Solar Industri');
                $table->decimal('price_per_liter', 12, 2);
                $table->date('effective_start');
                $table->date('effective_end')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 10. Area Approvers table (Assign SPV, Manager, Vendor, Fuel Man to areas)
        if (!Schema::hasTable('area_approvers')) {
            Schema::create('area_approvers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->enum('role_type', ['spv', 'manager', 'vendor', 'fuelman']);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 11. User Signatures table
        if (!Schema::hasTable('user_signatures')) {
            Schema::create('user_signatures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('signature_path');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 12. Enhance Fuel Requests table
        Schema::table('fuel_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('fuel_requests', 'employee_id')) {
                $table->foreignId('employee_id')->nullable()->after('user_id')->constrained('employees')->nullOnDelete();
            }
            if (!Schema::hasColumn('fuel_requests', 'driver_nrp')) {
                $table->string('driver_nrp')->nullable()->after('driver_name');
                $table->string('driver_position')->nullable()->after('driver_nrp');
                $table->string('driver_department')->nullable()->after('driver_position');
                $table->string('source_company')->nullable()->after('company');
            }
            if (!Schema::hasColumn('fuel_requests', 'vehicle_id')) {
                $table->foreignId('vehicle_id')->nullable()->after('unit_code')->constrained('vehicles')->nullOnDelete();
            }
            if (!Schema::hasColumn('fuel_requests', 'unit_type')) {
                $table->string('unit_type')->nullable()->after('police_no');
            }
            if (!Schema::hasColumn('fuel_requests', 'area_id')) {
                $table->foreignId('area_id')->nullable()->after('area')->constrained('areas')->nullOnDelete();
            }
            if (!Schema::hasColumn('fuel_requests', 'vendor_id')) {
                $table->foreignId('vendor_id')->nullable()->after('vendor_name')->constrained('vendors')->nullOnDelete();
            }
            if (!Schema::hasColumn('fuel_requests', 'vendor_user_id')) {
                $table->foreignId('vendor_user_id')->nullable()->after('vendor_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('fuel_requests', 'manager_id')) {
                $table->foreignId('manager_id')->nullable()->after('spv_name')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('fuel_requests', 'manager_name')) {
                $table->string('manager_name')->nullable()->after('manager_id');
            }
            if (!Schema::hasColumn('fuel_requests', 'planned_fuel_time')) {
                $table->dateTime('planned_fuel_time')->nullable()->after('request_date');
            }
            if (!Schema::hasColumn('fuel_requests', 'meter_type')) {
                $table->enum('meter_type', ['km', 'hm'])->default('km')->after('hm_km');
            }
            if (!Schema::hasColumn('fuel_requests', 'actual_liters')) {
                $table->decimal('actual_liters', 8, 2)->nullable()->after('total_liters');
                $table->decimal('actual_hm_km', 10, 2)->nullable()->after('actual_liters');
                $table->dateTime('fueled_at')->nullable()->after('actual_hm_km');
            }
            if (!Schema::hasColumn('fuel_requests', 'notes')) {
                $table->text('notes')->nullable()->after('area');
            }
            if (!Schema::hasColumn('fuel_requests', 'status_manager')) {
                $table->string('status_manager')->default('-')->after('spv_note');
                $table->timestamp('manager_action_at')->nullable()->after('status_manager');
                $table->text('manager_note')->nullable()->after('manager_action_at');
            }
            if (!Schema::hasColumn('fuel_requests', 'status_vendor')) {
                $table->string('status_vendor')->default('-')->after('manager_note');
                $table->timestamp('vendor_action_at')->nullable()->after('status_vendor');
                $table->text('vendor_note')->nullable()->after('vendor_action_at');
            }
            if (!Schema::hasColumn('fuel_requests', 'vendor_signature')) {
                $table->string('vendor_signature')->nullable()->after('depthead_signature');
            }
            if (!Schema::hasColumn('fuel_requests', 'current_stage')) {
                $table->string('current_stage')->default('pending_spv')->after('overall_status'); // draft, pending_spv, pending_manager, pending_vendor, approved, fueling, completed, rejected, cancelled
            }
        });

        // 13. Fuel Request Approvals audit table
        if (!Schema::hasTable('fuel_request_approvals')) {
            Schema::create('fuel_request_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fuel_request_id')->constrained('fuel_requests')->cascadeOnDelete();
                $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
                $table->enum('stage', ['spv', 'manager', 'vendor']);
                $table->enum('status', ['approved', 'rejected']);
                $table->text('note')->nullable();
                $table->string('signature_path')->nullable();
                $table->timestamp('action_at');
                $table->timestamps();
            });
        }

        // 14. Fuel Transactions table (Pencatatan realisasi pengisian oleh Fuel Man)
        if (!Schema::hasTable('fuel_transactions')) {
            Schema::create('fuel_transactions', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_no')->unique();
                $table->foreignId('fuel_request_id')->constrained('fuel_requests')->cascadeOnDelete();
                $table->foreignId('fuelman_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('requested_liters', 8, 2);
                $table->decimal('actual_liters', 8, 2);
                $table->decimal('variance_liters', 8, 2)->default(0); // actual - requested
                $table->decimal('meter_at_fueling', 10, 2); // HM / KM aktual
                $table->dateTime('fueled_at');
                $table->string('fuelman_signature')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 15. Fuel Request History / Audit Trail table
        if (!Schema::hasTable('fuel_request_history')) {
            Schema::create('fuel_request_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fuel_request_id')->nullable()->constrained('fuel_requests')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action'); // created, approved_spv, approved_manager, approved_vendor, rejected, fueling, completed, updated, master_updated, evaluation_calculated, report_exported
                $table->string('from_status')->nullable();
                $table->string('to_status')->nullable();
                $table->text('note')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 16. Fuel Evaluations table (Evaluasi konsumsi BBM & pemborosan)
        if (!Schema::hasTable('fuel_evaluations')) {
            Schema::create('fuel_evaluations', function (Blueprint $table) {
                $table->id();
                $table->string('evaluation_no')->unique();
                $table->string('period'); // e.g. '2026-05' or '2025-05'
                $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
                $table->decimal('meter_start', 10, 2)->nullable();
                $table->decimal('meter_end', 10, 2)->nullable();
                $table->decimal('total_distance_or_hours', 10, 2)->default(0);
                $table->decimal('total_actual_liters', 10, 2)->default(0);
                $table->decimal('total_standard_liters', 10, 2)->default(0);
                $table->decimal('actual_ratio', 10, 2)->default(0);
                $table->string('standard_type')->default('km_per_liter');
                $table->decimal('standard_used', 8, 2)->default(0); // KM/L or L/hr
                $table->decimal('tolerance_used', 5, 2)->default(5.00);
                $table->decimal('price_used', 12, 2)->default(0); // Rp / Liter
                $table->decimal('total_actual_cost', 14, 2)->default(0); // actual_liters * price
                $table->decimal('variance_liters', 10, 2)->default(0); // actual - standard
                $table->decimal('waste_liters', 10, 2)->default(0); // max(variance, 0)
                $table->decimal('estimated_cost_loss', 14, 2)->default(0); // waste * price
                $table->enum('evaluation_status', ['normal', 'needs_review', 'incomplete', 'invalid_data'])->default('normal');
                $table->text('notes')->nullable();
                $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('calculated_at')->useCurrent();
                $table->timestamps();
            });
        }

        // 17. Fuel Evaluation Transactions pivot table
        if (!Schema::hasTable('fuel_evaluation_transactions')) {
            Schema::create('fuel_evaluation_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fuel_evaluation_id')->constrained('fuel_evaluations')->cascadeOnDelete();
                $table->foreignId('fuel_transaction_id')->constrained('fuel_transactions')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        // 18. User Notifications table
        if (!Schema::hasTable('user_notifications')) {
            Schema::create('user_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('fuel_request_id')->nullable()->constrained('fuel_requests')->cascadeOnDelete();
                $table->string('title');
                $table->text('message');
                $table->string('type')->default('info'); // info, success, warning, danger
                $table->boolean('is_read')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_evaluation_transactions');
        Schema::dropIfExists('fuel_evaluations');
        Schema::dropIfExists('fuel_request_history');
        Schema::dropIfExists('fuel_transactions');
        Schema::dropIfExists('fuel_request_approvals');
        Schema::dropIfExists('user_signatures');
        Schema::dropIfExists('area_approvers');
        Schema::dropIfExists('fuel_prices');
        Schema::dropIfExists('fuel_standards');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('employees');
    }
};
