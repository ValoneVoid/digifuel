<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Vehicle;
use App\Models\Area;
use App\Models\AreaApprover;
use App\Models\Vendor;
use App\Models\User;

class StoreFuelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $isDepthead = $this->user()?->isDepthead();
        $isSpbu = strtoupper(trim((string) $this->input('vendor_name'))) === 'SPBU';
        $isDraft = $this->input('action_type') === 'draft';

        return [
            'request_date' => ['required', 'date'],
            'planned_fuel_time' => ['nullable', 'date'],
            'unit_code' => ['required', 'string', 'exists:vehicles,unit_code'],
            'police_no' => ['required', 'string'],
            'total_liters' => ['required', 'numeric', 'gt:0', 'max:5000'],
            'hm_km' => ['required', 'numeric', 'min:0'],
            'area' => [$isSpbu ? 'nullable' : 'required', 'string'],
            'support_point_id' => ['nullable', 'exists:support_points,id'],
            'spv_id' => [($isDepthead || $isSpbu) ? 'nullable' : 'required', 'exists:users,id'],
            'fuelman_id' => ['nullable', 'exists:users,id'],
            'depthead_id' => [($isDepthead || $isSpbu) ? 'nullable' : 'required', 'exists:users,id'],
            'vendor_name' => ['required', 'string'],
            'spbu_location' => [$isSpbu ? 'required' : 'nullable', 'string', 'max:255'],
            'spbu_price_per_liter' => [$isSpbu ? 'required' : 'nullable', 'numeric', 'gt:0', 'max:1000000'],
            'spbu_receipt_photo' => [($isSpbu && !$isDraft) ? 'required' : 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'action_type' => ['nullable', 'in:submit,draft'],
        ];
    }

    public function messages(): array
    {
        return [
            'request_date.required' => 'Tanggal pengajuan BBM wajib diisi.',
            'request_date.date' => 'Format tanggal pengajuan tidak valid.',
            'planned_fuel_time.date' => 'Format rencana waktu pengisian tidak valid.',
            'unit_code.required' => 'Unit Code kendaraan wajib dipilih.',
            'unit_code.exists' => 'Unit Code kendaraan yang dipilih tidak terdaftar pada sistem.',
            'police_no.required' => 'Nomor polisi kendaraan wajib diisi.',
            'total_liters.required' => 'Total (jumlah) liter BBM wajib diisi.',
            'total_liters.numeric' => 'Total liter BBM harus berupa angka numerik.',
            'total_liters.gt' => 'Total liter BBM harus lebih besar dari 0 Liter.',
            'total_liters.max' => 'Total liter BBM tidak boleh lebih dari 5.000 Liter per pengajuan.',
            'hm_km.required' => 'HM / KM pengajuan wajib diisi.',
            'hm_km.numeric' => 'HM / KM harus berupa angka numerik valid.',
            'hm_km.min' => 'Nilai HM / KM tidak boleh bernilai negatif.',
            'area.required' => 'Area operasional wajib dipilih.',
            'support_point_id.exists' => 'Support Point yang dipilih tidak valid atau sedang nonaktif.',
            'spv_id.required' => 'Approver SPV wajib dipilih.',
            'spv_id.exists' => 'Approver SPV yang dipilih tidak valid atau sedang nonaktif.',
            'fuelman_id.required' => 'Petugas Fuel Man wajib dipilih.',
            'fuelman_id.exists' => 'Petugas Fuel Man yang dipilih tidak valid atau sedang nonaktif.',
            'depthead_id.required' => 'Approver Dept Head / Manager wajib dipilih.',
            'depthead_id.exists' => 'Approver Dept Head / Manager yang dipilih tidak valid atau sedang nonaktif.',
            'vendor_name.required' => 'Vendor pengisian BBM wajib dipilih.',
            'spbu_location.required' => 'Lokasi SPBU wajib diisi saat memilih pengisian SPBU.',
            'spbu_price_per_liter.required' => 'Harga aktual per liter di SPBU wajib diisi.',
            'spbu_price_per_liter.numeric' => 'Harga aktual per liter harus berupa angka.',
            'spbu_price_per_liter.gt' => 'Harga aktual per liter harus lebih dari 0.',
            'spbu_receipt_photo.required' => 'Foto nota / struk SPBU wajib diunggah.',
            'spbu_receipt_photo.image' => 'Bukti nota SPBU harus berupa file gambar (JPG, PNG, WEBP).',
            'spbu_receipt_photo.max' => 'Ukuran file foto nota maksimal 8 MB (sistem akan mengompres otomatis).',
            'notes.max' => 'Catatan keperluan BBM maksimal 1.000 karakter.',
        ];
    }

    public function attributes(): array
    {
        return [
            'request_date' => 'Tanggal & Waktu Pengisian',
            'unit_code' => 'Unit Code (Kode Unit)',
            'police_no' => 'Nomor Polisi (Police No)',
            'total_liters' => 'Total (Jumlah) Liter BBM',
            'hm_km' => 'KM / HM Odometer',
            'area' => 'Area Operasional',
            'support_point_id' => 'Support Point',
            'spv_id' => 'Pilihan Approval SPV',
            'depthead_id' => 'Pilihan Approval Dept Head / Manager',
            'vendor_name' => 'Perusahaan Vendor BBM',
            'spbu_location' => 'Lokasi SPBU',
            'spbu_price_per_liter' => 'Harga Aktual per Liter (SPBU)',
            'spbu_receipt_photo' => 'Foto Nota SPBU',
            'notes' => 'Catatan Tambahan',
            'signature' => 'Tanda Tangan Digital',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            /** @var \App\Models\User|null $authUser */
            $authUser = $this->user();
            if ($authUser) {
                if (!$authUser->is_active) {
                    $validator->errors()->add('user_id', 'Akun pengguna Anda sedang nonaktif dan tidak dapat mengajukan Fuel Request baru.');
                }
                if ($authUser->employee_id) {
                    $employee = $authUser->employee()->withTrashed()->first();
                    if (!$employee || !$employee->is_active || $employee->trashed()) {
                        $validator->errors()->add('employee_id', 'Data karyawan tidak aktif dan tidak dapat digunakan untuk pengajuan Fuel Request baru.');
                    }
                }
                if ($this->input('action_type') !== 'draft' && !$authUser->hasActiveSignature()) {
                    $validator->errors()->add('signature', 'Anda wajib mengunggah tanda tangan digital di menu Profil sebelum dapat mengajukan Fuel Request.');
                }
            }

            if ($this->filled('unit_code')) {
                $vehicle = Vehicle::where('unit_code', $this->input('unit_code'))->first();
                if (!$vehicle || !$vehicle->is_active) {
                    $validator->errors()->add('unit_code', 'Unit kendaraan tidak aktif atau tidak dapat digunakan untuk pengajuan baru.');
                }
            }

            $area = null;
            if ($this->filled('area')) {
                $area = Area::where('name', $this->input('area'))->where('is_active', true)->first();
                if (!$area) {
                    $validator->errors()->add('area', 'Area operasional tidak ditemukan atau sedang nonaktif.');
                } else if ($area->activeSupportPoints()->exists()) {
                    if (empty($this->input('support_point_id'))) {
                        $validator->errors()->add('support_point_id', 'Area ' . $area->name . ' memiliki titik operasional (Support Point). Silakan pilih Support Point.');
                    } else {
                        $spExists = $area->activeSupportPoints()->where('id', $this->input('support_point_id'))->exists();
                        if (!$spExists) {
                            $validator->errors()->add('support_point_id', 'Support Point yang dipilih tidak valid atau tidak termasuk dalam area ' . $area->name . '.');
                        }
                    }
                }
            }

            $isSpbu = strtoupper(trim((string) $this->input('vendor_name'))) === 'SPBU';

            if ($this->filled('vendor_name')) {
                if ($isSpbu) {
                    if (!$authUser?->canUseSpbu()) {
                        $validator->errors()->add('vendor_name', 'Opsi pengisian SPBU hanya tersedia khusus untuk Dept Head / Site Manager (SM).');
                    }
                    if ($this->hasFile('spbu_receipt_photo')) {
                        $imgInfo = @getimagesize($this->file('spbu_receipt_photo')->getRealPath());
                        if ($imgInfo === false || !in_array($imgInfo['mime'] ?? '', ['image/jpeg', 'image/png', 'image/webp'], true)) {
                            $validator->errors()->add('spbu_receipt_photo', 'File foto nota SPBU bukan gambar JPG, PNG, atau WEBP yang valid.');
                        }
                    }
                } else {
                    $vendor = Vendor::where('name', $this->input('vendor_name'))->where('is_active', true)->first();
                    if (!$vendor) {
                        $validator->errors()->add('vendor_name', 'Vendor pengisian BBM tidak ditemukan atau sedang nonaktif.');
                    }
                }
            }

            if (!$authUser?->isDepthead() && !$isSpbu) {
                if ($this->filled('spv_id')) {
                    $spv = User::find($this->input('spv_id'));
                    if (!$spv || !$spv->is_active) {
                        $validator->errors()->add('spv_id', 'Approver SPV yang dipilih sedang nonaktif.');
                    }
                }

                if ($this->filled('depthead_id')) {
                    $depthead = User::find($this->input('depthead_id'));
                    if (!$depthead || !$depthead->is_active) {
                        $validator->errors()->add('depthead_id', 'Approver Dept Head/Manager yang dipilih sedang nonaktif.');
                    }
                }
            }

            if ($this->filled('fuelman_id')) {
                $fuelman = User::find($this->input('fuelman_id'));
                if (!$fuelman || !$fuelman->is_active) {
                    $validator->errors()->add('fuelman_id', 'Fuel Man yang dipilih sedang nonaktif.');
                }
            }

            if ($area && !$authUser?->isDepthead()) {
                if ($this->filled('spv_id')) {
                    $spvAssignedInArea = AreaApprover::where('area_id', $area->id)->where('role_type', 'spv')->exists();
                    $spvExplicitlyInactive = AreaApprover::where('area_id', $area->id)
                        ->where('user_id', $this->input('spv_id'))
                        ->where('role_type', 'spv')
                        ->where('is_active', false)
                        ->exists();

                    if ($spvExplicitlyInactive || ($spvAssignedInArea && !AreaApprover::where('area_id', $area->id)
                        ->where('user_id', $this->input('spv_id'))
                        ->where('role_type', 'spv')
                        ->where('is_active', true)
                        ->exists())) {
                        $validator->errors()->add('spv_id', 'Approver SPV yang dipilih tidak aktif atau tidak ditugaskan pada area operasional tersebut.');
                    }
                }

                if ($this->filled('depthead_id')) {
                    $mgrAssignedInArea = AreaApprover::where('area_id', $area->id)->where('role_type', 'manager')->exists();
                    $mgrExplicitlyInactive = AreaApprover::where('area_id', $area->id)
                        ->where('user_id', $this->input('depthead_id'))
                        ->where('role_type', 'manager')
                        ->where('is_active', false)
                        ->exists();

                    if ($mgrExplicitlyInactive || ($mgrAssignedInArea && !AreaApprover::where('area_id', $area->id)
                        ->where('user_id', $this->input('depthead_id'))
                        ->where('role_type', 'manager')
                        ->where('is_active', true)
                        ->exists())) {
                        $validator->errors()->add('depthead_id', 'Approver Dept Head/Manager yang dipilih tidak aktif atau tidak ditugaskan pada area operasional tersebut.');
                    }
                }

                if ($this->filled('fuelman_id')) {
                    $fuelmanExplicitlyInactive = AreaApprover::where('area_id', $area->id)
                        ->where('user_id', $this->input('fuelman_id'))
                        ->where('role_type', 'fuelman')
                        ->where('is_active', false)
                        ->exists();

                    if ($fuelmanExplicitlyInactive) {
                        $validator->errors()->add('fuelman_id', 'Penugasan Fuel Man pada area operasional tersebut sedang nonaktif.');
                    }
                }
            }
        });
    }
}

