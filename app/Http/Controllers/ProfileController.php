<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserSignature;
use App\Models\FuelRequestHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    protected function getActiveUser(): User
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }
        return $user;
    }

    public function index()
    {
        $user = $this->getActiveUser();
        $signatures = UserSignature::where('user_id', $user->id)->latest()->get();
        return view('profile.index', compact('user', 'signatures'));
    }

    /**
     * Update data profil pengguna (CRUD Profil)
     */
    public function updateProfile(Request $request)
    {
        $user = $this->getActiveUser();

        if ($request->filled('phone')) {
            $request->merge([
                'phone' => preg_replace('/[^0-9]/', '', (string)$request->input('phone')),
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => [
                'required',
                'string',
                'max:80',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:25', 'regex:/^08[0-9]{8,12}$/'],
            'nrp' => ['nullable', 'string', 'max:50'],
            'jabatan' => ['required', 'string', 'max:100'],
            'company' => ['required', 'string', 'max:150'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username tersebut sudah digunakan oleh akun lain.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Email tersebut sudah terdaftar pada akun lain.',
            'phone.regex' => 'Nomor HP / WhatsApp harus diawali dengan 08 dan terdiri dari 10-14 digit angka (contoh: 081234567890).',
            'jabatan.required' => 'Jabatan wajib diisi.',
            'company.required' => 'Nama perusahaan / site wajib diisi.',
            'password.min' => 'Password baru minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->nrp = $validated['nrp'] ?? $user->nrp;
        $user->jabatan = $validated['jabatan'];
        $user->company = $validated['company'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // Sinkronisasi ke tabel employees apabila akun terhubung ke data Employee
        if ($user->employee) {
            $empUpdate = [
                'name' => $validated['name'],
                'position' => $validated['jabatan'],
                'company' => $validated['company'],
            ];
            if (!empty($validated['nrp'])) {
                $nrpExists = \App\Models\Employee::where('nrp', $validated['nrp'])
                    ->where('id', '!=', $user->employee->id)
                    ->exists();
                if (!$nrpExists) {
                    $empUpdate['nrp'] = $validated['nrp'];
                }
            }
            $user->employee->update($empUpdate);
        }

        FuelRequestHistory::record(
            null,
            $user->id,
            'profile_updated',
            null,
            'active',
            "Pengguna {$user->name} memperbarui informasi profil akun."
        );

        return redirect()->route('profile.index')->with('success', 'Data profil berhasil diperbarui!');
    }

    /**
     * Tambah atau ganti foto profil pengguna
     */
    public function uploadPhoto(Request $request)
    {
        $user = $this->getActiveUser();

        $request->validate([
            'profile_photo' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:3072'],
        ], [
            'profile_photo.required' => 'Silakan pilih file foto profil terlebih dahulu.',
            'profile_photo.image' => 'File yang diunggah harus berupa gambar.',
            'profile_photo.mimes' => 'Format foto profil harus berupa PNG, JPG, JPEG, atau WEBP.',
            'profile_photo.max' => 'Ukuran foto profil maksimal 3 MB.',
        ]);

        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');

            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo === false || !in_array($imageInfo['mime'] ?? '', ['image/png', 'image/jpeg', 'image/webp'])) {
                return redirect()->route('profile.index')
                    ->with('error', 'File ditolak: Konten file bukan gambar PNG, JPEG, atau WEBP yang valid.');
            }

            $mimeToExt = [
                'image/png' => 'png',
                'image/jpeg' => 'jpg',
                'image/webp' => 'webp',
            ];
            $ext = $mimeToExt[$imageInfo['mime']] ?? 'jpg';
            $filename = 'avatar_user_' . $user->id . '_' . time() . '.' . $ext;

            // Hapus file foto profil lama jika ada
            $this->removeOldPhotoFiles($user->profile_photo_url);

            $privateDir = storage_path('app/private/profile_photos');
            if (!file_exists($privateDir)) {
                mkdir($privateDir, 0755, true);
            }
            $file->move($privateDir, $filename);

            $publicDir = public_path('uploads/profile_photos');
            if (!file_exists($publicDir)) {
                mkdir($publicDir, 0755, true);
            }
            @copy($privateDir . DIRECTORY_SEPARATOR . $filename, $publicDir . DIRECTORY_SEPARATOR . $filename);

            $photoUrl = 'uploads/profile_photos/' . $filename;
            $hadPhotoBefore = !empty($user->profile_photo_url);

            $user->profile_photo_url = $photoUrl;
            $user->save();

            FuelRequestHistory::record(
                null,
                $user->id,
                $hadPhotoBefore ? 'profile_photo_updated' : 'profile_photo_added',
                null,
                'active',
                "Pengguna {$user->name} " . ($hadPhotoBefore ? 'mengganti' : 'menambahkan') . " foto profil ({$filename})."
            );

            return redirect()->route('profile.index')->with(
                'success',
                $hadPhotoBefore ? 'Foto profil berhasil diganti!' : 'Foto profil berhasil ditambahkan!'
            );
        }

        return redirect()->route('profile.index')->with('error', 'Gagal mengunggah foto profil.');
    }

    /**
     * Hapus foto profil pengguna
     */
    public function deletePhoto()
    {
        $user = $this->getActiveUser();

        if (empty($user->profile_photo_url)) {
            return redirect()->route('profile.index')->with('error', 'Belum ada foto profil untuk dihapus.');
        }

        $this->removeOldPhotoFiles($user->profile_photo_url);

        $user->profile_photo_url = null;
        $user->save();

        FuelRequestHistory::record(
            null,
            $user->id,
            'profile_photo_deleted',
            null,
            'deleted',
            "Pengguna {$user->name} menghapus foto profil."
        );

        return redirect()->route('profile.index')->with('success', 'Foto profil berhasil dihapus.');
    }

    /**
     * Serve profile photo image
     */
    public function servePhoto(string $filename)
    {
        $safeName = basename($filename);
        $publicPath = public_path('uploads/profile_photos/' . $safeName);
        if (file_exists($publicPath)) {
            return response()->file($publicPath);
        }

        $privatePath = storage_path('app/private/profile_photos/' . $safeName);
        if (file_exists($privatePath)) {
            return response()->file($privatePath);
        }

        abort(404);
    }

    protected function removeOldPhotoFiles(?string $photoUrl): void
    {
        if (empty($photoUrl)) {
            return;
        }
        $safeName = basename($photoUrl);
        $publicFile = public_path('uploads/profile_photos/' . $safeName);
        if (file_exists($publicFile) && is_file($publicFile)) {
            @unlink($publicFile);
        }
        $privateFile = storage_path('app/private/profile_photos/' . $safeName);
        if (file_exists($privateFile) && is_file($privateFile)) {
            @unlink($privateFile);
        }
    }

    public function uploadSignature(Request $request)
    {
        $user = $this->getActiveUser();

        $request->validate([
            'signature' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ], [
            'signature.required' => 'Silakan pilih file gambar tanda tangan.',
            'signature.image' => 'File yang diunggah harus berupa gambar asli (bukan sekadar berganti ekstensi).',
            'signature.mimes' => 'Format tanda tangan harus berupa PNG atau JPEG/JPG.',
            'signature.max' => 'Ukuran file tanda tangan maksimal 2 MB.',
        ]);

        if ($request->hasFile('signature')) {
            $file = $request->file('signature');

            // Additional deep image content verification to reject fake extensions
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo === false || !in_array($imageInfo['mime'] ?? '', ['image/png', 'image/jpeg'])) {
                return redirect()->route('profile.index')
                    ->with('error', 'File ditolak: Konten file bukan gambar PNG atau JPEG yang valid.');
            }

            $ext = $imageInfo['mime'] === 'image/png' ? 'png' : 'jpg';
            $filename = 'sig_user_' . $user->id . '_' . time() . '.' . $ext;

            // Store in private storage disk
            $privateDir = storage_path('app/private/signatures');
            if (!file_exists($privateDir)) {
                mkdir($privateDir, 0755, true);
            }
            $file->move($privateDir, $filename);

            // Also copy to public/uploads/signatures for seamless printable sheet rendering
            $publicDir = public_path('uploads/signatures');
            if (!file_exists($publicDir)) {
                mkdir($publicDir, 0755, true);
            }
            @copy($privateDir . DIRECTORY_SEPARATOR . $filename, $publicDir . DIRECTORY_SEPARATOR . $filename);

            // Deactivate previous signatures in history without deleting files used on historical documents
            UserSignature::where('user_id', $user->id)->update(['is_active' => false]);

            $signatureUrl = 'signatures/file/' . $filename;
            UserSignature::create([
                'user_id' => $user->id,
                'signature_path' => $signatureUrl,
                'is_active' => true,
            ]);

            $user->signature_url = $signatureUrl;
            $user->save();

            FuelRequestHistory::record(
                null,
                $user->id,
                'signature_uploaded',
                null,
                'active',
                "Pengguna {$user->name} memperbarui tanda tangan digital ({$filename})."
            );

            return redirect()->route('profile.index')->with('success', 'Tanda tangan digital berhasil diunggah dan disimpan!');
        }

        return redirect()->route('profile.index')->with('error', 'Gagal mengunggah tanda tangan.');
    }

    public function deactivateSignature($id)
    {
        $user = $this->getActiveUser();
        $sig = UserSignature::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $sig->update(['is_active' => false]);

        if ($user->signature_url === $sig->signature_path) {
            $latestActive = UserSignature::where('user_id', $user->id)->where('is_active', true)->latest()->first();
            $user->update(['signature_url' => $latestActive?->signature_path]);
        }

        return redirect()->route('profile.index')->with('success', 'Tanda tangan lama telah dinonaktifkan (riwayat dokumen lama tetap terjaga).');
    }

    /**
     * Hapus tanda tangan aktif atau item riwayat tanda tangan
     */
    public function deleteSignature($id)
    {
        $user = $this->getActiveUser();

        if ($id === 'current') {
            UserSignature::where('user_id', $user->id)->where('is_active', true)->update(['is_active' => false]);
            $user->update(['signature_url' => null]);

            return redirect()->route('profile.index')->with('success', 'Tanda tangan aktif berhasil dihapus dari profil.');
        }

        $sig = UserSignature::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $wasCurrent = ($user->signature_url === $sig->signature_path);
        $sig->delete();

        if ($wasCurrent) {
            $nextActive = UserSignature::where('user_id', $user->id)->where('is_active', true)->latest()->first();
            $user->update(['signature_url' => $nextActive?->signature_path]);
        }

        return redirect()->route('profile.index')->with('success', 'Data tanda tangan berhasil dihapus.');
    }

    /**
     * Serve signature image securely from private storage
     */
    public function serveSignature(string $filename)
    {
        $safeName = basename($filename);
        $privatePath = storage_path('app/private/signatures/' . $safeName);
        if (file_exists($privatePath)) {
            return response()->file($privatePath);
        }

        $publicPath = public_path('uploads/signatures/' . $safeName);
        if (file_exists($publicPath)) {
            return response()->file($publicPath);
        }

        abort(404);
    }
}
