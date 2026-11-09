<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function signatures()
    {
        return $this->hasMany(UserSignature::class);
    }

    public function fuelRequests()
    {
        return $this->hasMany(FuelRequest::class);
    }

    public function spvRequests()
    {
        return $this->hasMany(FuelRequest::class, 'spv_id');
    }

    public function deptheadRequests()
    {
        return $this->hasMany(FuelRequest::class, 'depthead_id');
    }

    public function fuelmanRequests()
    {
        return $this->hasMany(FuelRequest::class, 'fuelman_id');
    }

    public function approvals()
    {
        return $this->hasMany(FuelRequestApproval::class, 'approver_id');
    }

    public function fuelTransactions()
    {
        return $this->hasMany(FuelTransaction::class, 'fuelman_id');
    }

    public function hasRole(string|array $roles): bool
    {
        $roleList = is_array($roles) ? $roles : [$roles];
        if (in_array($this->role, $roleList, true)) {
            return true;
        }
        return $this->roles()->whereIn('name', $roleList)->exists();
    }

    public function areaApprovers()
    {
        return $this->hasMany(AreaApprover::class);
    }

    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class)->latest();
    }

    public function unreadNotificationsCount(): int
    {
        return $this->userNotifications()->where('is_read', false)->count();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->roles()->where('name', 'admin')->exists();
    }

    public function isDriver(): bool
    {
        return in_array($this->role, ['driver', 'employee']) || $this->roles()->whereIn('name', ['driver', 'employee'])->exists();
    }

    public function isEmployee(): bool
    {
        return $this->isDriver();
    }

    public function isSpv(): bool
    {
        return $this->role === 'spv' || $this->roles()->where('name', 'spv')->exists();
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['manager', 'depthead']) || $this->roles()->whereIn('name', ['manager', 'depthead'])->exists();
    }

    public function isDepthead(): bool
    {
        return $this->isManager();
    }

    public function isVendor(): bool
    {
        return $this->role === 'vendor' || $this->roles()->where('name', 'vendor')->exists();
    }

    public function isFuelman(): bool
    {
        return $this->role === 'fuelman' || $this->roles()->where('name', 'fuelman')->exists();
    }

    public function isApprover(): bool
    {
        return $this->isSpv() || $this->isManager() || $this->isVendor() || $this->isFuelman() || $this->isAdmin();
    }

    public function hasActiveSignature(): bool
    {
        return !empty($this->signature_url);
    }
}
