<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelRequest extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'request_date' => 'datetime',
        'planned_fuel_time' => 'datetime',
        'fueled_at' => 'datetime',
        'spv_action_at' => 'datetime',
        'manager_action_at' => 'datetime',
        'depthead_action_at' => 'datetime',
        'vendor_action_at' => 'datetime',
        'fuelman_action_at' => 'datetime',
        'total_liters' => 'decimal:2',
        'actual_liters' => 'decimal:2',
        'actual_hm_km' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function areaModel()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function vendorModel()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function unitVendor()
    {
        return $this->belongsTo(UnitVendor::class, 'unit_vendor_id');
    }

    public function spv()
    {
        return $this->belongsTo(User::class, 'spv_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function depthead()
    {
        return $this->belongsTo(User::class, 'depthead_id');
    }

    public function vendorUser()
    {
        return $this->belongsTo(User::class, 'vendor_user_id');
    }

    public function fuelman()
    {
        return $this->belongsTo(User::class, 'fuelman_id');
    }

    public function approvals()
    {
        return $this->hasMany(FuelRequestApproval::class)->orderBy('action_at', 'asc');
    }

    public function transactions()
    {
        return $this->hasMany(FuelTransaction::class)->orderBy('fueled_at', 'desc');
    }

    public function latestTransaction()
    {
        return $this->hasOne(FuelTransaction::class)->latestOfMany('fueled_at');
    }

    public function histories()
    {
        return $this->hasMany(FuelRequestHistory::class)->orderBy('created_at', 'asc');
    }

    public function getStageLabelAttribute(): string
    {
        return match ($this->current_stage) {
            'draft' => 'Draft',
            'pending_spv' => 'Menunggu Persetujuan SPV',
            'pending_manager' => 'Menunggu Persetujuan Dept Head/Manager',
            'pending_vendor' => 'Menunggu Persetujuan Vendor',
            'approved' => 'Approved - Ready for Fueling',
            'fueling' => 'Sedang Pengisian BBM (Fueling)',
            'completed' => 'Selesai ( Pengisian BBM Tercatat )',
            'rejected' => 'Ditolak',
            'cancelled' => 'Dibatalkan',
            default => ucfirst(str_replace('_', ' ', $this->current_stage ?? 'pending_spv')),
        };
    }

    public function isVendorApproved(): bool
    {
        return in_array($this->current_stage, ['approved', 'fueling', 'completed'])
            || $this->status_vendor === 'disetujui'
            || ($this->status_spv === 'disetujui' && $this->status_depthead === 'disetujui' && $this->status_fuelman === 'disetujui');
    }
}
