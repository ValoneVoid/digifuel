<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request and enforce strict role-based access control (Default Deny).
     *
     * Does NOT automatically grant access to Admin unless 'admin' is explicitly listed in $roles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user || !$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login' => 'Akun Anda sedang tidak aktif. Silakan hubungi Administrator.',
            ]);
        }

        // Parse and normalize required roles from middleware parameters (e.g. 'role:spv,depthead')
        $requiredRoles = [];
        foreach ($roles as $roleGroup) {
            foreach (explode(',', $roleGroup) as $r) {
                $normalized = strtolower(trim($r));
                if ($normalized !== '') {
                    $requiredRoles[] = $normalized;
                }
            }
        }

        // Default deny if no roles are specified
        if (empty($requiredRoles)) {
            abort(403, 'Akses Ditolak: Hak akses halaman belum dikonfigurasi.');
        }

        // Collect user's actual role identifiers from database column `users.role` and relation `roles` (`user_roles`)
        $userRoles = [];
        if (!empty($user->role)) {
            $userRoles[] = strtolower(trim((string) $user->role));
        }

        $pivotRoles = $user->roles()->pluck('name')->map(fn ($name) => strtolower(trim((string) $name)))->all();
        $userRoles = array_values(array_unique(array_merge($userRoles, $pivotRoles)));

        // Expand canonical role aliases used in DigiFuel domain (depthead <-> manager, driver <-> employee)
        $expandedUserRoles = $userRoles;
        if (in_array('depthead', $userRoles, true) || in_array('manager', $userRoles, true)) {
            $expandedUserRoles[] = 'depthead';
            $expandedUserRoles[] = 'manager';
        }
        if (in_array('driver', $userRoles, true) || in_array('employee', $userRoles, true)) {
            $expandedUserRoles[] = 'driver';
            $expandedUserRoles[] = 'employee';
        }
        $expandedUserRoles = array_values(array_unique($expandedUserRoles));

        // Strict intersection check (no automatic Admin bypass unless 'admin' is in $requiredRoles)
        foreach ($requiredRoles as $requiredRole) {
            if (in_array($requiredRole, $expandedUserRoles, true)) {
                return $next($request);
            }
        }

        abort(403, 'Akses Ditolak: Role Anda tidak memiliki izin untuk mengakses modul atau fitur ini.');
    }
}

