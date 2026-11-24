<?php

namespace App\Providers;

use App\Models\FuelRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production') || request()->header('X-Forwarded-Proto') === 'https') {
            URL::forceScheme('https');
        }

        // Role-scoped pending approval & final verification badge counters for Navbar (Item 11)
        View::composer('layouts.app', function ($view) {
            $pendingApprovalCount = 0;
            $pendingUserVerifyCount = 0;

            try {
                /** @var \App\Models\User|null $user */
                $user = Auth::user();
                if ($user && Schema::hasTable('fuel_requests')) {
                    if ($user->isAdmin()) {
                        $pendingApprovalCount = FuelRequest::whereIn('current_stage', [
                            'pending_spv',
                            'pending_manager',
                            'pending_vendor',
                        ])->count();
                    } elseif ($user->isSpv()) {
                        $pendingApprovalCount = FuelRequest::where('status_spv', 'menunggu')
                            ->where(function ($q) use ($user) {
                                $q->where('spv_id', $user->id)->orWhereNull('spv_id');
                            })
                            ->count();
                    } elseif ($user->isManager()) {
                        $pendingApprovalCount = FuelRequest::where('status_spv', 'disetujui')
                            ->where('status_depthead', 'menunggu')
                            ->where(function ($q) use ($user) {
                                $q->where('depthead_id', $user->id)
                                  ->orWhere('manager_id', $user->id)
                                  ->orWhereNull('depthead_id');
                            })
                            ->count();
                    } elseif ($user->isVendor()) {
                        $q = FuelRequest::where('status_spv', 'disetujui')
                            ->where('status_depthead', 'disetujui')
                            ->where('current_stage', 'pending_vendor');
                        $q->where(function ($sub) use ($user) {
                            if ($user->vendor_id) {
                                $sub->where('vendor_id', $user->vendor_id);
                            }
                            if ($user->vendor) {
                                $sub->orWhere('vendor_name', $user->vendor->name);
                            }
                        });
                        $pendingApprovalCount = $q->count();
                    } elseif ($user->isFuelman()) {
                        $pendingApprovalCount = FuelRequest::whereIn('current_stage', [
                            'pending_vendor',
                            'approved',
                            'fueling',
                        ])->count();
                    }

                    // Count requests owned by this user waiting for Final Verification (Final Approve)
                    $pendingUserVerifyCount = FuelRequest::where('user_id', $user->id)
                        ->where('current_stage', 'pending_user_verification')
                        ->count();
                }
            } catch (\Throwable $e) {
                $pendingApprovalCount = 0;
                $pendingUserVerifyCount = 0;
            }

            $view->with([
                'pendingApprovalCount' => $pendingApprovalCount,
                'pendingUserVerifyCount' => $pendingUserVerifyCount,
            ]);
        });
    }
}
