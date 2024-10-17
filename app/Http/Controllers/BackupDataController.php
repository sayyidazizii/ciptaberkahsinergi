<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class BackupDataController extends Controller
{
    public function index(){
        $date = date('d M Y H:i:s');
        return view('content.Backup.index',compact('date'));
    }

    public function store(){
        // Gunakan DB transaction untuk memastikan backup berjalan sempurna
        DB::beginTransaction();

        try {
            // Buat nama file backup berdasarkan tanggal dan jam saat ini
            $filename = 'backup-' . date('Y-m-d_H-i-s') . '.sql';

            // Tentukan path untuk menyimpan file backup
            $storagePath = storage_path('app/backup/');
            
            // Buat direktori jika belum ada
            if (!file_exists($storagePath)) {
                mkdir($storagePath, 0755, true);
            }

            // Perintah untuk membackup database
            $database = env('DB_DATABASE');
            $username = env('DB_USERNAME');
            $password = env('DB_PASSWORD');
            $host = env('DB_HOST');
            
            // Gunakan mysqldump untuk backup
            $command = "mysqldump --user={$username} --password={$password} --host={$host} {$database} > {$storagePath}{$filename} 2>&1";
            
            // Eksekusi perintah
            $output = null;
            $return_var = null;
            exec($command, $output, $return_var);

            // Cek jika eksekusi command berhasil
            if ($return_var !== 0) {
                // Jika terjadi error pada proses backup
                throw new \Exception("Backup gagal dilakukan. Command mysqldump error code: {$return_var} | Output: " . implode("\n", $output));
            }

            // Commit transaksi jika semua berjalan lancar
            DB::commit();

            // Path lengkap ke file backup
            $filePath = $storagePath . $filename;

            // Mengirim file sebagai response download
            return response()->download($filePath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            // Rollback transaksi jika terjadi error
            DB::rollBack();

            // Log error ke file log Laravel
            Log::error('Backup gagal: ' . $e->getMessage());

            // Kembalikan pesan error ke view
            return back()->with('error', 'Backup gagal dilakukan. Error: ' . $e->getMessage());
        }
    }
}
