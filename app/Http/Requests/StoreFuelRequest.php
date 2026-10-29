<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Vehicle;
use App\Models\Area;
use App\Models\AreaApprover;

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
            $vehicle = Vehicle::where('unit_code', $this->input('unit_code'))->first();
            if (!$vehicle || !$vehicle->is_active) {
                $validator->errors()->add('unit_code', 'Unit kendaraan tidak aktif atau tidak dapat digunakan untuk pengajuan baru.');
            }

            $area = Area::where('name', $this->input('area'))->where('is_active', true)->first();
            if ($area && AreaApprover::where('area_id', $area->id)->where('is_active', true)->exists()) {
                $spvValid = AreaApprover::where('area_id', $area->id)
                    ->where('user_id', $this->input('spv_id'))
                    ->where('role_type', 'spv')
                    ->where('is_active', true)
                    ->exists();

                $mgrValid = AreaApprover::where('area_id', $area->id)
                    ->where('user_id', $this->input('depthead_id'))
                    ->where('role_type', 'manager')
                    ->where('is_active', true)
                    ->exists();

                if (!$spvValid) {
                    $validator->errors()->add('spv_id', 'Approver SPV yang dipilih tidak ditugaskan pada area operasional tersebut.');
                }
                if (!$mgrValid) {
                    $validator->errors()->add('depthead_id', 'Approver Dept Head/Manager yang dipilih tidak ditugaskan pada area operasional tersebut.');
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
            'hm_km.required' => 'Nilai HM / KM wajib diisi.',
            'hm_km.numeric' => 'Nilai HM / KM harus berupa angka.',
            'hm_km.min' => 'Nilai HM / KM tidak boleh negatif.',
            'area.required' => 'Area operasional wajib dipilih.',
            'spv_id.required' => 'Approver SPV wajib dipilih.',
            'fuelman_id.required' => 'Approver Fuel Man wajib dipilih.',
            'depthead_id.required' => 'Approver Dept Head wajib dipilih.',
            'vendor_name.required' => 'Vendor pengisian wajib dipilih.',
        ];
    }
}

