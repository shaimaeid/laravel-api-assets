<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/****  BackupLogs custom artisan command******/
/*
Run the following command to generate a new Artisan command:
php artisan make:command BackupLogs
Register the custom Artisan command in the app/Console/Kernel.php file
Use the command as follow
php artisan logs:backup
*/
class BackupLogs extends Command
{
    protected $signature = 'logs:backup';
    protected $description = 'Backup log files, compress them into a zip file, and download it';

    public function handle()
    {
        // Get all log files from the storage/logs directory
        $logFiles = glob(storage_path('logs/*.log'));

        if (count($logFiles) === 0) {
            $this->info('No log files found.');
            return;
        }

        // Create a new zip archive
        $zipFileName = 'log-' . now()->format('Y-m-d_H-i-s') . '.zip';
        $zip = new ZipArchive;
        $zip->open(storage_path('app/' . $zipFileName), ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Add log files to the zip archive
        foreach ($logFiles as $logFile) {
            $zip->addFile($logFile, basename($logFile));
        }

        // Close the zip archive
        $zip->close();

        // Delete the original log files
        foreach ($logFiles as $logFile) {
            unlink($logFile);
        }

        $this->info('Log files backed up and compressed successfully.');

        // Download the zip file
        $localFilePath = storage_path('app/' . $zipFileName);
        $fileName = pathinfo($localFilePath, PATHINFO_BASENAME);
        return response()->download($localFilePath, $fileName)->deleteFileAfterSend();
    }
}
