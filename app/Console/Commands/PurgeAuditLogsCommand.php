<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FuelRequestHistory;
use Carbon\Carbon;

class PurgeAuditLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:purge {--days=180 : Jumlah hari retensi log yang dipertahankan} {--force : Jalankan pembersihan tanpa konfirmasi interaktif}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bersihkan log audit trail yang sudah melewati batas retensi hari untuk menjaga efisiensi database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        if ($days < 30) {
            $this->error('Batas retensi minimal adalah 30 hari demi alasan audit dan keamanan data.');
            return 1;
        }

        $cutoffDate = Carbon::now()->subDays($days);
        $count = FuelRequestHistory::where('created_at', '<', $cutoffDate)->count();

        if ($count === 0) {
            $this->info("Tidak ada log audit yang lebih lama dari {$days} hari (sebelum {$cutoffDate->format('d/m/Y H:i')}).");
            return 0;
        }

        if (!$this->option('force')) {
            $confirmed = $this->confirm("Ditemukan {$count} log audit sebelum tanggal {$cutoffDate->format('d/m/Y H:i')}. Apakah Anda yakin ingin menghapus data ini?");
            if (!$confirmed) {
                $this->warn('Pembersihan dibatalkan oleh pengguna.');
                return 0;
            }
        }

        $deleted = FuelRequestHistory::where('created_at', '<', $cutoffDate)->delete();

        // Rekam aksi pembersihan ke dalam audit trail baru sebagai akuntabilitas
        FuelRequestHistory::record(
            null,
            null,
            'audit_purged',
            null,
            'purged',
            "Pembersihan otomatis retensi: {$deleted} log audit sebelum {$cutoffDate->format('d/m/Y H:i')} (> {$days} hari) berhasil dibersihkan dari database."
        );

        $this->info("Berhasil membersihkan {$deleted} log audit yang berusia lebih dari {$days} hari.");
        return 0;
    }
}
