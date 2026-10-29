<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\FuelRequestHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
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
        $user = User::where('username', $loginInput)
            ->orWhere('email', $loginInput)
            ->orWhere('nrp', $loginInput)
            ->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return back()
                ->withInput($request->only('login'))
                ->withErrors(['login' => 'Username/Email atau Password yang Anda masukkan tidak sesuai.']);
        }

        if (!$user->is_active) {
            return back()
                ->withInput($request->only('login'))
                ->withErrors(['login' => 'Akun Anda sedang dinonaktifkan oleh Administrator.']);
        }

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
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);
        Auth::login($user);

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
        $user = Auth::user() ?? User::where('username', 'adji')->first();
        $notif = UserNotification::where('id', $id)->where('user_id', $user?->id)->firstOrFail();
        $notif->update(['is_read' => true]);

        if ($notif->fuel_request_id) {
            return redirect()->route('approval.show', $notif->fuel_request_id);
        }

        return redirect()->back();
    }

    protected function redirectByRole(User $user, string $message)
    {
        if ($user->isAdmin()) {
            return redirect()->route('evaluation.index')->with('success', $message);
        }

        if ($user->isApprover()) {
            return redirect()->route('approval.index')->with('success', $message);
        }

        return redirect()->route('fuel.create')->with('success', $message);
    }
}
