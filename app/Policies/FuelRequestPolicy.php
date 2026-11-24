<?php

namespace App\Policies;

use App\Models\FuelRequest;
use App\Models\User;

class FuelRequestPolicy
{
    /**
     * Determine whether the user can view a specific Fuel Request (prevents IDOR across Employees & Vendors).
     */
    public function view(User $user, FuelRequest $fuelRequest): bool
    {
        if ($user->isAdmin() || $user->isSpv() || $user->isManager() || $user->isFuelman()) {
            return true;
        }

        if ($user->isVendor()) {
            if ($user->vendor_id && $fuelRequest->vendor_id) {
                return (int) $user->vendor_id === (int) $fuelRequest->vendor_id;
            }
            if ($user->vendor && $fuelRequest->vendor_name) {
                return strcasecmp($user->vendor->name, $fuelRequest->vendor_name) === 0;
            }
            return false;
        }

        // Employee / Driver can only view their own requests
        return (int) $fuelRequest->user_id === (int) $user->id;
    }

    /**
     * Determine whether the user can update or submit a Draft Fuel Request.
     */
    public function update(User $user, FuelRequest $fuelRequest): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return ($user->isDriver() || $user->isSpv()) && (int) $fuelRequest->user_id === (int) $user->id;
    }

    /**
     * Determine whether the user can cancel a Fuel Request.
     */
    public function cancel(User $user, FuelRequest $fuelRequest): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return ($user->isDriver() || $user->isSpv()) && (int) $fuelRequest->user_id === (int) $user->id;
    }

    /**
     * Determine whether the user can view/print/download the Fuel Request Document.
     */
    public function printDocument(User $user, FuelRequest $fuelRequest): bool
    {
        return $this->view($user, $fuelRequest);
    }

    /**
     * Determine whether the user can generate/download the official PDF Fuel Request Sheet.
     * Strictly requires both view permission and final Vendor approval.
     */
    public function generatePdf(User $user, FuelRequest $fuelRequest): bool
    {
        if (!$this->printDocument($user, $fuelRequest)) {
            return false;
        }

        return $fuelRequest->isVendorApproved();
    }

    /**
     * Determine whether the user can perform approval/rejection on a Fuel Request.
     */
    public function approve(User $user, FuelRequest $fuelRequest): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isVendor()) {
            return $this->view($user, $fuelRequest);
        }

        return $user->isSpv() || $user->isManager() || $user->isFuelman();
    }

    /**
     * Determine whether the user can inspect and update requested fuel liters
     * (SPV on pending_spv stage, Dept Head/Manager on pending_manager stage, or Admin).
     */
    public function updateLiters(User $user, FuelRequest $fuelRequest): bool
    {
        if ($user->isAdmin()) {
            return $fuelRequest->status_spv === 'menunggu'
                || ($fuelRequest->status_spv === 'disetujui' && $fuelRequest->status_depthead === 'menunggu');
        }

        if ($user->isSpv() && $fuelRequest->status_spv === 'menunggu') {
            return empty($fuelRequest->spv_id) || (int) $fuelRequest->spv_id === (int) $user->id;
        }

        if ($user->isManager() && $fuelRequest->status_spv === 'disetujui' && $fuelRequest->status_depthead === 'menunggu') {
            return empty($fuelRequest->depthead_id) || (int) $fuelRequest->depthead_id === (int) $user->id;
        }

        return false;
    }

    /**
     * Determine whether the requesting user / driver can perform Final Verification (Final Approve).
     */
    public function verifyUser(User $user, FuelRequest $fuelRequest): bool
    {
        if ($fuelRequest->current_stage !== 'pending_user_verification') {
            return false;
        }

        return (int) $fuelRequest->user_id === (int) $user->id || $user->isAdmin();
    }

    /**
     * Determine whether the user can record fueling realization.
     * Once realized, Vendor/Fuelman cannot re-edit (locked).
     */
    public function recordRealization(User $user, FuelRequest $fuelRequest): bool
    {
        if (!$user->isAdmin() && ($fuelRequest->transactions()->exists() || in_array($fuelRequest->current_stage, ['pending_user_verification', 'completed'], true))) {
            return false;
        }

        return ($user->isVendor() || $user->isFuelman() || $user->isAdmin())
            && ($fuelRequest->status_depthead === 'disetujui' || $fuelRequest->isVendorApproved());
    }

    /**
     * Determine whether the user can delete a Fuel Request (Trial/Junk/Cancelled/Draft cleanup).
     *
     * Rules:
     * 1. Cannot delete if physical fueling transaction already recorded (current_stage === 'completed'/'pending_user_verification' or transactions exist).
     * 2. Administrator can delete requests that have no physical fueling transactions.
     * 3. Driver (the requester) can delete their own request IF it is still a 'draft', 'cancelled',
     *    or 'pending_spv' (stage 1) before SPV approval.
     */
    public function delete(User $user, FuelRequest $fuelRequest): bool
    {
        if ($fuelRequest->transactions()->exists() || in_array($fuelRequest->current_stage, ['pending_user_verification', 'completed'], true)) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (($user->isDriver() || $user->isSpv()) && (int) $fuelRequest->user_id === (int) $user->id) {
            return in_array($fuelRequest->current_stage, ['draft', 'cancelled'])
                || ($fuelRequest->status_spv === 'menunggu' && in_array($fuelRequest->current_stage, ['pending_spv', 'submitted']));
        }

        return false;
    }
}
