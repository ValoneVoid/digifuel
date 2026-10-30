<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserSignature;
use App\Models\FuelRequestHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

            return redirect()->route('profile.index')->with('success', 'Tanda tangan digital berhasil diunggah dan disimpan di penyimpanan privat!');
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
