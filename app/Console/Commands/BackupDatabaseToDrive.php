<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupDatabaseToDrive extends Command
{
    protected $signature = 'app:backup-database';
    protected $description = 'Backup DB to Google Drive';

    public function handle()
    {
        $filename = 'ekinpay-app' . Carbon::now()->format('Y-m-d_H-i-s') . '.sql';
        $localPath = storage_path("app/{$filename}");

        // Update with your DB credentials if needed
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s',
            env('DB_USERNAME'),
            env('DB_PASSWORD'),
            env('DB_HOST'),
            env('DB_DATABASE'),
            $localPath
        );

        exec($command);

        if (file_exists($localPath)) {
            Storage::disk('google')->put($filename, file_get_contents($localPath));
            unlink($localPath);
            $this->info('Database backup uploaded to Google Drive.');
        } else {
            $this->error('Failed to create backup.');
        }
    }
}
