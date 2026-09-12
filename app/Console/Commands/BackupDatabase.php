<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

#[Signature('app:backup-database')]
#[Description('Create database backup in storage/app/backups')]
class BackupDatabase extends Command
{
    public function handle(): int
    {
        if (! in_array(config('database.default'), ['mysql', 'mariadb'], true)) {
            $this->error('Database backup supports mysql or mariadb only.');

            return self::FAILURE;
        }

        $connection = config('database.connections.'.config('database.default'));
        $directory = storage_path('app/backups');
        if (! is_dir($directory)) {
            mkdir($directory, 0750, true);
        }

        $path = $directory.'/simano-'.now()->format('Ymd-His').'.sql';
        $handle = fopen($path, 'wb');
        $process = new Process([
            env('DB_DUMP_BINARY', 'mysqldump'),
            '--host='.$connection['host'],
            '--port='.$connection['port'],
            '--user='.$connection['username'],
            '--single-transaction',
            '--routines',
            '--skip-lock-tables',
            $connection['database'],
        ], base_path(), ['MYSQL_PWD' => (string) $connection['password']]);
        $process->setTimeout((int) env('DB_BACKUP_TIMEOUT', 600));
        $exitCode = $process->run(function (string $type, string $buffer) use ($handle): void {
            if ($type === Process::OUT) {
                fwrite($handle, $buffer);
            }
        });
        fclose($handle);

        if ($exitCode !== 0) {
            @unlink($path);
            $this->error($process->getErrorOutput());

            return self::FAILURE;
        }

        $this->info('Backup created: '.$path);

        return self::SUCCESS;
    }
}
