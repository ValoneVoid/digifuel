<?php

namespace App\Policies;

use App\Models\FuelRequest;
use App\Models\User;

class FuelRequestPolicy
{
    /**
     * Determine whether the user can view a specific Fuel Request (prevents IDOR across Employees & Vendors)
     */
    public function view(User $user, FuelRequest $fuelRequest): bool
    {
        if ($user->isAdmin() || $user->isSpv() || $user->isManager() || $user->isFuelman()) {
            return true;
        }

        if ($user->isVendor()) {
            if ($user->vendor_id && $fuelRequest->vendor_id) {
                return (int)$user->vendor_id === (int)$fuelRequest->vendor_id;
            }
            if ($user->vendor && $fuelRequest->vendor_name) {
                return strcasecmp($user->vendor->name, $fuelRequest->vendor_name) === 0;
            }
            return true;
        }

        // Employee / Driver can only view their own requests
        return (int)$fuelRequest->user_id === (int)$user->id;
    }

    /**
     * Determine whether the user can print the final Fuel Request Document
     */
    public function printDocument(User $user, FuelRequest $fuelRequest): bool
    {
        return $this->view($user, $fuelRequest);
    }

    /**
     * Determine whether the user can record fueling realization
     */
    public function recordRealization(User $user, FuelRequest $fuelRequest): bool
    {
        return ($user->isFuelman() || $user->isAdmin()) && $fuelRequest->isVendorApproved();
    }
}

