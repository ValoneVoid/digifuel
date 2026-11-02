<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Vehicle;
use App\Models\Area;
use App\Models\AreaApprover;
use App\Models\Vendor;
use App\Models\User;

class StoreFuelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'request_date' => ['required', 'date'],
            'planned_fuel_time' => ['nullable', 'date'],
            'unit_code' => ['required', 'string', 'exists:vehicles,unit_code'],
            'police_no' => ['required', 'string'],
            'total_liters' => ['required', 'numeric', 'gt:0', 'max:5000'],
            'hm_km' => ['required', 'numeric', 'min:0'],
            'area' => ['required', 'string'],
            'spv_id' => ['required', 'exists:users,id'],
            'fuelman_id' => ['required', 'exists:users,id'],
            'depthead_id' => ['required', 'exists:users,id'],
            'vendor_name' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'action_type' => ['nullable', 'in:submit,draft'],
        ];
    }

    public function withValidator($validator): void
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
            }

            $vehicle = Vehicle::where('unit_code', $this->input('unit_code'))->first();
            if (!$vehicle || !$vehicle->is_active) {
                $validator->errors()->add('unit_code', 'Unit kendaraan tidak aktif atau tidak dapat digunakan untuk pengajuan baru.');
            }

            $area = Area::where('name', $this->input('area'))->where('is_active', true)->first();
            if (!$area) {
                $validator->errors()->add('area', 'Area operasional tidak ditemukan atau sedang nonaktif.');
            }

            $vendor = Vendor::where('name', $this->input('vendor_name'))->where('is_active', true)->first();
            if (!$vendor) {
                $validator->errors()->add('vendor_name', 'Vendor pengisian BBM tidak ditemukan atau sedang nonaktif.');
            }

            $spv = User::find($this->input('spv_id'));
            if (!$spv || !$spv->is_active) {
                $validator->errors()->add('spv_id', 'Approver SPV yang dipilih sedang nonaktif.');
            }

            $depthead = User::find($this->input('depthead_id'));
            if (!$depthead || !$depthead->is_active) {
                $validator->errors()->add('depthead_id', 'Approver Dept Head/Manager yang dipilih sedang nonaktif.');
            }

            $fuelman = User::find($this->input('fuelman_id'));
            if (!$fuelman || !$fuelman->is_active) {
                $validator->errors()->add('fuelman_id', 'Fuel Man yang dipilih sedang nonaktif.');
            }

            if ($area) {
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

                $fuelmanExplicitlyInactive = AreaApprover::where('area_id', $area->id)
                    ->where('user_id', $this->input('fuelman_id'))
                    ->where('role_type', 'fuelman')
                    ->where('is_active', false)
                    ->exists();

                if ($fuelmanExplicitlyInactive) {
                    $validator->errors()->add('fuelman_id', 'Penugasan Fuel Man pada area operasional tersebut sedang nonaktif.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'request_date.required' => 'Tanggal & waktu pengajuan wajib diisi.',
            'unit_code.required' => 'Unit Code wajib dipilih.',
            'unit_code.exists' => 'Unit Code tidak ditemukan dalam data master.',
            'police_no.required' => 'Nomor polisi wajib terisi.',
            'total_liters.required' => 'Jumlah liter BBM wajib diisi.',
            'total_liters.gt' => 'Jumlah liter BBM harus lebih besar dari nol (0).',
            'hm_km.required' => 'Nilai KM wajib diisi.',
            'hm_km.numeric' => 'Nilai KM harus berupa angka.',
            'hm_km.min' => 'Nilai KM tidak boleh negatif.',
            'area.required' => 'Area operasional wajib dipilih.',
            'spv_id.required' => 'Approver SPV wajib dipilih.',
            'fuelman_id.required' => 'Approver Fuel Man wajib dipilih.',
            'depthead_id.required' => 'Approver Dept Head wajib dipilih.',
            'vendor_name.required' => 'Vendor pengisian wajib dipilih.',
        ];
    }
}

