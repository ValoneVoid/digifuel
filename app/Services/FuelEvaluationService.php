<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\FuelEvaluation;
use App\Models\FuelEvaluationTransaction;
use App\Models\FuelPrice;
use App\Models\FuelStandard;
use App\Models\FuelTransaction;
use App\Models\FuelRequestHistory;
use Illuminate\Support\Facades\DB;

class FuelEvaluationService
{
    /**
     * Evaluate fuel consumption for a single vehicle and period (YYYY-MM)
     * Uses real FuelTransaction records or explicit meter start/end values.
     */
    public function evaluateVehicle(
        Vehicle $vehicle,
        string $period,
        ?int $userId = null,
        ?float $overrideMeterStart = null,
        ?float $overrideMeterEnd = null,
        ?float $overrideActualLiters = null
    ): FuelEvaluation {
        return DB::transaction(function () use ($vehicle, $period, $userId, $overrideMeterStart, $overrideMeterEnd, $overrideActualLiters) {
            // Find transactions in this period for this vehicle
            $transactions = FuelTransaction::whereHas('fuelRequest', function ($q) use ($vehicle) {
                $q->where('vehicle_id', $vehicle->id)
                  ->orWhere('unit_code', $vehicle->unit_code);
            })
            ->where('fueled_at', 'like', "{$period}%")
            ->orderBy('fueled_at', 'asc')
            ->orderBy('meter_at_fueling', 'asc')
            ->get();

            // Determine active standard for this vehicle
            $standard = FuelStandard::where('vehicle_id', $vehicle->id)
                ->where('is_active', true)
                ->orderBy('effective_start', 'desc')
                ->first();

            // Determine active fuel price for this period
            $priceRecord = FuelPrice::getCurrentPrice("{$period}-15") ?? FuelPrice::where('is_active', true)->latest('effective_start')->first();
            $pricePerLiter = $priceRecord ? (float)$priceRecord->price_per_liter : 27000.00;

            $meterStart = $overrideMeterStart;
            $meterEnd = $overrideMeterEnd;
            $totalActualLiters = $overrideActualLiters;

            if ($transactions->count() > 0) {
                if ($meterStart === null) {
                    $firstRequestHmKm = (float)($transactions->first()->fuelRequest->hm_km ?? 0);
                    $firstFuelingMeter = (float)$transactions->first()->meter_at_fueling;
                    $meterStart = ($firstRequestHmKm > 0 && $firstRequestHmKm < $firstFuelingMeter)
                        ? $firstRequestHmKm
                        : $firstFuelingMeter;
                }
                if ($meterEnd === null) {
                    $meterEnd = (float)$transactions->last()->meter_at_fueling;
                }
                if ($totalActualLiters === null) {
                    $totalActualLiters = (float)$transactions->sum('actual_liters');
                }
            }

            $standardType = $standard ? $standard->standard_type : ($vehicle->meter_type === 'hm' ? 'liter_per_hour' : 'km_per_liter');
            $standardValue = $standard ? (float)$standard->standard_value : 0.0;
            $tolerancePct = $standard ? (float)$standard->tolerance_percentage : 5.0;

            // Default evaluation outputs
            $distanceOrHours = 0.0;
            $standardLiters = 0.0;
            $actualRatio = 0.0;
            $varianceLiters = 0.0;
            $wasteLiters = 0.0;
            $estimatedCostLoss = 0.0;
            $totalActualCost = round(($totalActualLiters ?? 0) * $pricePerLiter, 2);
            $status = 'normal';
            $notes = null;

            // Rule A: Validate completeness & meter validity
            if ($meterStart === null || $meterEnd === null || $totalActualLiters === null || $totalActualLiters <= 0 || $standardValue <= 0) {
                $status = 'incomplete';
                $notes = 'Data meter awal/akhir, transaksi BBM aktual, atau standar konsumsi unit belum lengkap pada periode ini.';
            } elseif ($meterEnd < $meterStart) {
                $status = 'invalid_data';
                $notes = "Nilai meter akhir ({$meterEnd}) lebih kecil daripada meter awal ({$meterStart}). Perhitungan biaya kerugian dinonaktifkan.";
            } elseif ($meterEnd == $meterStart) {
                $status = 'incomplete';
                $notes = 'Selisih jarak tempuh atau jam operasi masih 0 (membutuhkan minimal 2 pembacaan meter berbeda).';
            } else {
                // Valid meter range
                $distanceOrHours = round($meterEnd - $meterStart, 2);

                if ($standardType === 'km_per_liter') {
                    // Rule B: Standar KM per liter -> BBM standar = Jarak tempuh / Standar KM/L
                    $standardLiters = round($distanceOrHours / $standardValue, 2);
                    $actualRatio = round($distanceOrHours / $totalActualLiters, 2);
                } else {
                    // Rule C: Standar liter per jam -> BBM standar = Jam operasi * Standar L/jam
                    $standardLiters = round($distanceOrHours * $standardValue, 2);
                    $actualRatio = round($totalActualLiters / $distanceOrHours, 2);
                }

                // Rule D: Estimasi pemborosan
                $varianceLiters = round($totalActualLiters - $standardLiters, 2);
                $wasteLiters = max($varianceLiters, 0.0);
                $estimatedCostLoss = round($wasteLiters * $pricePerLiter, 2);

                // Rule E: Status evaluasi berdasarkan batas toleransi
                $allowedMaxLiters = round($standardLiters * (1 + ($tolerancePct / 100.0)), 2);
                if ($totalActualLiters > $allowedMaxLiters) {
                    $status = 'needs_review';
                    $notes = "Konsumsi aktual ({$totalActualLiters} L) melebihi batas toleransi {$tolerancePct}% ({$allowedMaxLiters} L). Terdeteksi pemborosan {$wasteLiters} L.";
                } else {
                    $status = 'normal';
                    $notes = "Konsumsi BBM berada dalam batas wajar sesuai standar perusahaan.";
                }
            }

            $evalNo = sprintf('EVAL-%s-%s', str_replace('-', '', $period), preg_replace('/[^A-Za-z0-9]/', '', $vehicle->unit_code));

            $evaluation = FuelEvaluation::updateOrCreate(
                [
                    'period' => $period,
                    'vehicle_id' => $vehicle->id,
                ],
                [
                    'evaluation_no' => $evalNo,
                    'meter_start' => $meterStart,
                    'meter_end' => $meterEnd,
                    'total_distance_or_hours' => $distanceOrHours,
                    'total_actual_liters' => $totalActualLiters ?? 0,
                    'total_standard_liters' => $standardLiters,
                    'actual_ratio' => $actualRatio,
                    'standard_type' => $standardType,
                    'standard_used' => $standardValue,
                    'tolerance_used' => $tolerancePct,
                    'price_used' => $pricePerLiter,
                    'total_actual_cost' => $totalActualCost,
                    'variance_liters' => $varianceLiters,
                    'waste_liters' => $wasteLiters,
                    'estimated_cost_loss' => $estimatedCostLoss,
                    'evaluation_status' => $status,
                    'notes' => $notes,
                    'calculated_by' => $userId,
                    'calculated_at' => now(),
                ]
            );

            // Sync source transactions in fuel_evaluation_transactions
            if ($transactions->count() > 0) {
                FuelEvaluationTransaction::where('fuel_evaluation_id', $evaluation->id)->delete();
                foreach ($transactions as $trx) {
                    FuelEvaluationTransaction::create([
                        'fuel_evaluation_id' => $evaluation->id,
                        'fuel_transaction_id' => $trx->id,
                    ]);
                }
            }

            return $evaluation;
        });
    }

    /**
     * Recalculate evaluations for all active vehicles in a given period
     */
    public function evaluatePeriod(string $period, ?int $userId = null): int
    {
        $vehicles = Vehicle::where('is_active', true)->get();
        $count = 0;

        foreach ($vehicles as $vehicle) {
            $hasTransactions = FuelTransaction::whereHas('fuelRequest', function ($q) use ($vehicle) {
                $q->where('vehicle_id', $vehicle->id)->orWhere('unit_code', $vehicle->unit_code);
            })->where('fueled_at', 'like', "{$period}%")->exists();

            $existing = FuelEvaluation::where('vehicle_id', $vehicle->id)->where('period', $period)->first();

            if ($hasTransactions) {
                $this->evaluateVehicle($vehicle, $period, $userId);
                $count++;
            } elseif ($existing) {
                // Re-evaluate existing evaluation record keeping its meter readings
                $this->evaluateVehicle(
                    $vehicle,
                    $period,
                    $userId,
                    (float)$existing->meter_start,
                    (float)$existing->meter_end,
                    (float)$existing->total_actual_liters
                );
                $count++;
            }
        }

        FuelRequestHistory::record(
            null,
            $userId,
            'evaluation_calculated',
            null,
            'completed',
            "Menjalankan perhitungan Evaluasi Konsumsi BBM untuk periode {$period} ({$count} unit dievaluasi)."
        );

        return $count;
    }
}

