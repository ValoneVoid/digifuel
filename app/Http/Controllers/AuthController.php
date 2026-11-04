<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\FuelRequestHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required' => 'Username atau Email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $loginInput = trim($request->input('login'));
        $throttleKey = Str::transliterate(Str::lower($loginInput).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()
                ->withInput($request->only('login'))
                ->withErrors(['login' => "Terlalu banyak percobaan login yang gagal. Silakan coba lagi dalam {$seconds} detik."]);
        }

        $user = User::where('username', $loginInput)
            ->orWhere('email', $loginInput)
            ->orWhere('nrp', $loginInput)
            ->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            return back()
                ->withInput($request->only('login'))
                ->withErrors(['login' => 'Username/Email atau Password yang Anda masukkan tidak sesuai.']);
        }

        if (!$user->is_active) {
            return back()
                ->withInput($request->only('login'))
                ->withErrors(['login' => 'Akun Anda sedang dinonaktifkan oleh Administrator.']);
        }

        RateLimiter::clear($throttleKey);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        FuelRequestHistory::record(
            null,
            $user->id,
            'user_login',
            null,
            'active',
            "Pengguna {$user->name} ({$user->jabatan}) berhasil login ke sistem."
        );

        return $this->redirectByRole($user, "Selamat datang kembali, {$user->name} ({$user->jabatan})!");
    }

    public function switchUser(Request $request)
    {
        if (!Auth::check()) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        if (!app()->environment(['local', 'testing'])) {
            abort(403, 'Fitur Role Switcher hanya diizinkan pada lingkungan lokal atau pengujian.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);

        if (!$user->is_active) {
            abort(403, 'Akun tujuan sedang dinonaktifkan.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectByRole($user, "Beralih ke akun {$user->name} ({$user->jabatan})");
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil logout dari sistem DigiFuel.');
    }

    public function readNotification(Request $request, $id)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        $notif = UserNotification::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $notif->update(['is_read' => true]);

        if ($notif->fuel_request_id) {
            if ($user->isDriver() && !$user->isAdmin()) {
                return redirect()->route('history.index');
            }
            return redirect()->route('approval.show', $notif->fuel_request_id);
        }

        return redirect()->back();
    }

    protected function redirectByRole(User $user, string $message)
    {
        return redirect()->route('dashboard')->with('success', $message);
    }
}
