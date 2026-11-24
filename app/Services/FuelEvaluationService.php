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
            $transactions = FuelTransaction::with('fuelRequest')
                ->whereHas('fuelRequest', function ($q) use ($vehicle) {
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
            $computedActualCost = null;

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
                    // Support actual SPBU price per liter when transaction came from SPBU
                    $computedActualCost = 0.0;
                    foreach ($transactions as $tx) {
                        $txFr = $tx->fuelRequest;
                        $unitPrice = ($txFr && $txFr->isSpbu() && (float)$txFr->spbu_price_per_liter > 0)
                            ? (float)$txFr->spbu_price_per_liter
                            : $pricePerLiter;
                        $computedActualCost += ((float)$tx->actual_liters * $unitPrice);
                    }
                    $computedActualCost = round($computedActualCost, 2);
                }
            }

            $standardType = $standard ? $standard->standard_type : ($vehicle->meter_type === 'hm' ? 'liter_per_hour' : 'km_per_liter');
            $standardValue = $standard ? (float)$standard->standard_value : 0.0;
            $standardMax = ($standard && $standard->standard_max !== null && (float)$standard->standard_max > $standardValue)
                ? (float)$standard->standard_max
                : null;
            $tolerancePct = $standard ? (float)$standard->tolerance_percentage : 5.0;

            // Default evaluation outputs
            $distanceOrHours = 0.0;
            $standardLiters = 0.0;
            $actualRatio = 0.0;
            $varianceLiters = 0.0;
            $wasteLiters = 0.0;
            $estimatedCostLoss = 0.0;
            $totalActualCost = $computedActualCost !== null ? $computedActualCost : round(($totalActualLiters ?? 0) * $pricePerLiter, 2);
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

                    // Varian Pemborosan (L) sesuai Excel Perusahaan: IF(Rasio Aktual < Rasio Standar, Fuel Aktual - (Jarak / Rasio Standar), 0)
                    $varianceLiters = round($totalActualLiters - ($distanceOrHours / $standardValue), 2);
                    $wasteLiters = ($actualRatio < $standardValue) ? max(round($totalActualLiters - ($distanceOrHours / $standardValue), 2), 0.0) : 0.0;
                    $estimatedCostLoss = round($wasteLiters * $pricePerLiter, 2);

                    $rangeLabel = $standardMax !== null ? "{$standardValue} - {$standardMax} Km/L" : "{$standardValue} Km/L";

                    // Evaluasi per Unit mendukung Rentang Standar (Min - Max)
                    if ($actualRatio < ($standardValue - 0.5)) {
                        $status = 'needs_review';
                        $notes = "Tidak Wajar (Boros): Rasio aktual ({$actualRatio} Km/L) di bawah batas toleransi minimal (" . ($standardValue - 0.5) . " Km/L dari standar {$rangeLabel}). Varian pemborosan {$wasteLiters} L.";
                    } elseif ($standardMax !== null && $actualRatio > $standardMax) {
                        $status = 'needs_review';
                        $notes = "Tidak Wajar (Di Atas Batas Maksimal): Rasio aktual ({$actualRatio} Km/L) melebihi rentang standar ({$rangeLabel}). Periksa kemungkinan pengisian belum tercatat atau salah input odometer.";
                    } else {
                        $status = 'normal';
                        $notes = "Wajar: Rasio aktual ({$actualRatio} Km/L) berada dalam rentang standar operasional ({$rangeLabel}).";
                    }
                } else {
                    // Rule C: Standar liter per jam -> BBM standar = Jam operasi * Standar L/jam
                    $standardLiters = round($distanceOrHours * $standardValue, 2);
                    $actualRatio = round($totalActualLiters / $distanceOrHours, 2);
                    $varianceLiters = round($totalActualLiters - $standardLiters, 2);
                    $wasteLiters = max($varianceLiters, 0.0);
                    $estimatedCostLoss = round($wasteLiters * $pricePerLiter, 2);

                    $allowedMaxLiters = round($standardLiters * (1 + ($tolerancePct / 100.0)), 2);
                    if ($totalActualLiters > $allowedMaxLiters) {
                        $status = 'needs_review';
                        $notes = "Tidak Wajar: Konsumsi aktual ({$totalActualLiters} L) melebihi batas toleransi ({$allowedMaxLiters} L). Varian pemborosan {$wasteLiters} L.";
                    } else {
                        $status = 'normal';
                        $notes = "Wajar: Konsumsi BBM berada dalam batas standar perusahaan.";
                    }
                }
            }

            // =========================================================================
            // EVALUASI KESELURUHAN / KUMULATIF SEJAK AWAL TERCATAT (Item 12)
            // Rumus: (Odometer Terakhir - Odometer Awal Pertama) / Total Liter Keseluruhan
            // =========================================================================
            $allHistoryTx = FuelTransaction::with('fuelRequest')
                ->whereHas('fuelRequest', function ($q) use ($vehicle) {
                    $q->where('vehicle_id', $vehicle->id)
                      ->orWhere('unit_code', $vehicle->unit_code);
                })
                ->orderBy('fueled_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $cumMeterStart = null;
            $cumMeterEnd = null;
            $cumDistance = 0.0;
            $cumLiters = 0.0;
            $cumRatio = 0.0;
            $cumStatus = 'incomplete';

            if ($allHistoryTx->count() > 0) {
                $firstTx = $allHistoryTx->first();
                $lastTx = $allHistoryTx->last();
                $firstReqOdo = (float) ($firstTx->fuelRequest->hm_km ?? 0);
                $firstTxOdo = (float) $firstTx->meter_at_fueling;
                $cumMeterStart = ($firstReqOdo > 0 && $firstReqOdo < $firstTxOdo) ? $firstReqOdo : $firstTxOdo;
                $cumMeterEnd = (float) $lastTx->meter_at_fueling;
                $cumLiters = round((float) $allHistoryTx->sum('actual_liters'), 2);

                if ($cumMeterEnd < $cumMeterStart) {
                    $cumStatus = 'invalid_data';
                } elseif ($cumMeterEnd == $cumMeterStart || $cumLiters <= 0 || $standardValue <= 0) {
                    $cumStatus = 'incomplete';
                } else {
                    $cumDistance = round($cumMeterEnd - $cumMeterStart, 2);
                    if ($standardType === 'km_per_liter') {
                        $cumRatio = round($cumDistance / $cumLiters, 2);
                        if ($cumRatio < ($standardValue - 0.5)) {
                            $cumStatus = 'needs_review';
                        } elseif ($standardMax !== null && $cumRatio > $standardMax) {
                            $cumStatus = 'needs_review';
                        } else {
                            $cumStatus = 'normal';
                        }
                    } else {
                        $cumRatio = round($cumLiters / $cumDistance, 2);
                        $cumAllowed = round(($cumDistance * $standardValue) * (1 + ($tolerancePct / 100.0)), 2);
                        $cumStatus = $cumLiters > $cumAllowed ? 'needs_review' : 'normal';
                    }
                }
            } elseif ($meterStart !== null && $meterEnd !== null && $totalActualLiters !== null && $totalActualLiters > 0) {
                // Fallback when evaluated from manual meter overrides without FuelTransaction rows
                $cumMeterStart = $meterStart;
                $cumMeterEnd = $meterEnd;
                $cumDistance = $distanceOrHours;
                $cumLiters = (float) $totalActualLiters;
                $cumRatio = $actualRatio;
                $cumStatus = $status;
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
                    'standard_max_used' => $standardMax,
                    'tolerance_used' => $tolerancePct,
                    'price_used' => $pricePerLiter,
                    'total_actual_cost' => $totalActualCost,
                    'variance_liters' => $varianceLiters,
                    'waste_liters' => $wasteLiters,
                    'estimated_cost_loss' => $estimatedCostLoss,
                    'evaluation_status' => $status,
                    'cumulative_meter_start' => $cumMeterStart,
                    'cumulative_meter_end' => $cumMeterEnd,
                    'cumulative_distance' => $cumDistance,
                    'cumulative_liters' => $cumLiters,
                    'cumulative_ratio' => $cumRatio,
                    'cumulative_status' => $cumStatus,
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

    /**
     * Compute per-refueling (per pengisian) distance, ratio, and Wajar/Tidak Wajar status
     * using consecutive odometer deltas across sorted transactions.
     */
    public function computePerFillMetrics($transactions, float $stdMin, ?float $stdMax = null): array
    {
        $sorted = $transactions->sortBy('fueled_at')->values();
        $metrics = [];
        $prevOdo = null;

        foreach ($sorted as $tx) {
            $currOdo = (float) $tx->meter_at_fueling;
            $liters = (float) $tx->actual_liters;
            $distance = null;
            $ratio = null;
            $status = 'baseline';

            if ($prevOdo !== null && $currOdo > $prevOdo && $liters > 0) {
                $distance = round($currOdo - $prevOdo, 2);
                $ratio = round($distance / $liters, 2);

                if ($stdMin > 0) {
                    if ($ratio < ($stdMin - 0.5)) {
                        $status = 'needs_review';
                    } elseif ($stdMax !== null && $stdMax > $stdMin && $ratio > $stdMax) {
                        $status = 'needs_review';
                    } else {
                        $status = 'normal';
                    }
                } else {
                    $status = 'normal';
                }
            } elseif ($prevOdo !== null && $currOdo < $prevOdo) {
                $status = 'invalid_data';
            }

            $metrics[$tx->id] = [
                'prev_odo' => $prevOdo,
                'distance' => $distance,
                'ratio' => $ratio,
                'status' => $status,
            ];

            if ($currOdo > 0) {
                $prevOdo = $currOdo;
            }
        }

        return $metrics;
    }
}

