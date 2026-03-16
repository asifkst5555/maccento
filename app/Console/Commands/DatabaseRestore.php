<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use PDO;
use PDOException;
use Symfony\Component\Process\Process;

class DatabaseRestore extends Command
{
    protected $signature = 'system:db-restore {file : Backup filename under storage/app/backups}';
    protected $description = 'Restore a database backup from storage/app/backups.';

    public function handle(): int
    {
        $file = basename((string) $this->argument('file'));
        $path = 'backups/' . $file;

        if (!Storage::disk('local')->exists($path)) {
            $this->error('Backup file not found.');
            return self::FAILURE;
        }

        if (!preg_match('/\.(sql|sqlite)$/i', $file)) {
            $this->error('Unsupported backup file type.');
            return self::FAILURE;
        }

        $connection = config('database.default');
        $driver = (string) config("database.connections.{$connection}.driver");

        if ($driver === 'sqlite') {
            return $this->restoreSqlite($connection, $path);
        }

        if ($driver !== 'mysql') {
            $this->error("Database driver {$driver} is not supported for restore.");
            return self::FAILURE;
        }

        return $this->restoreMysql($connection, $path);
    }

    private function restoreSqlite(string $connection, string $path): int
    {
        $databasePath = (string) config("database.connections.{$connection}.database");
        if ($databasePath === '' || !is_file($databasePath)) {
            $this->error('SQLite database file not found.');
            return self::FAILURE;
        }

        $backupPath = Storage::disk('local')->path($path);
        if (!copy($backupPath, $databasePath)) {
            $this->error('Failed to restore SQLite database file.');
            return self::FAILURE;
        }

        $this->line('SQLite database restored.');
        return self::SUCCESS;
    }

    private function restoreMysql(string $connection, string $path): int
    {
        $config = config("database.connections.{$connection}");
        $database = (string) ($config['database'] ?? '');
        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');
        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (string) ($config['port'] ?? '3306');
        $mysqlPath = trim((string) config('system_health.backup_mysql_path', ''));

        if ($database === '' || $username === '') {
            $this->error('MySQL configuration is incomplete.');
            return self::FAILURE;
        }

        $backupPath = Storage::disk('local')->path($path);

        $command = [
            $mysqlPath !== '' ? $mysqlPath : 'mysql',
            '--host=' . $host,
            '--port=' . $port,
            '--user=' . $username,
            $database,
        ];

        if ($password !== '') {
            $command[] = '--password=' . $password;
        }

        $process = new Process($command, null, null, file_get_contents($backupPath));
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            $errorOutput = $process->getErrorOutput() ?: $process->getOutput();
            $notFound = str_contains($errorOutput, 'not found')
                || str_contains($errorOutput, 'No such file or directory')
                || $process->getExitCode() === 127;

            if ($notFound) {
                $this->warn('mysql client not available. Falling back to PHP restore.');
                return $this->restoreMysqlWithPdo($connection, $backupPath);
            }

            $this->error('mysql restore failed. Ensure mysql client is installed (or set SYSTEM_HEALTH_BACKUP_MYSQL_PATH) and DB credentials are correct.');
            $this->error($errorOutput);
            return self::FAILURE;
        }

        $this->line('MySQL database restored.');
        return self::SUCCESS;
    }

    private function restoreMysqlWithPdo(string $connection, string $backupPath): int
    {
        try {
            $db = \Illuminate\Support\Facades\DB::connection($connection);
            $pdo = $db->getPdo();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            $this->error('MySQL connection failed for PHP restore.');
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        if (!is_readable($backupPath)) {
            $this->error('Backup file is not readable.');
            return self::FAILURE;
        }

        try {
            $pdo->exec('SET foreign_key_checks = 0;');
            $pdo->beginTransaction();

            $handle = fopen($backupPath, 'r');
            if ($handle === false) {
                $this->error('Failed to open backup file for reading.');
                return self::FAILURE;
            }

            $statement = '';
            $inString = false;
            $stringChar = '';
            while (($line = fgets($handle)) !== false) {
                $trimmed = trim($line);
                if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
                    continue;
                }

                $length = strlen($line);
                for ($i = 0; $i < $length; $i++) {
                    $char = $line[$i];
                    if ($inString) {
                        if ($char === $stringChar) {
                            $escaped = $i > 0 && $line[$i - 1] === '\\';
                            if (!$escaped) {
                                $inString = false;
                                $stringChar = '';
                            }
                        }
                    } else {
                        if ($char === '\'' || $char === '"') {
                            $inString = true;
                            $stringChar = $char;
                        }
                    }

                    if ($char === ';' && !$inString) {
                        $statement .= substr($line, 0, $i + 1);
                        $trim = trim($statement);
                        if ($trim !== '') {
                            $pdo->exec($trim);
                        }
                        $statement = '';
                        $line = substr($line, $i + 1);
                        $length = strlen($line);
                        $i = -1;
                        continue;
                    }
                }
                $statement .= $line;
            }

            if (trim($statement) !== '') {
                $pdo->exec($statement);
            }

            fclose($handle);
            $pdo->commit();
            $pdo->exec('SET foreign_key_checks = 1;');
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->error('PHP restore failed while executing SQL.');
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->line('MySQL database restored via PHP.');
        return self::SUCCESS;
    }
}
