<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDO;
use PDOException;
use Symfony\Component\Process\Process;

class DatabaseBackup extends Command
{
    protected $signature = 'system:db-backup {--prune : Delete old backups after successful run} {--respect-settings : Honor backup schedule settings} {--force : Force backup regardless of schedule}';
    protected $description = 'Create a database backup (mysqldump or SQLite copy).';

    public function handle(): int
    {
        if ($this->option('respect-settings') && !$this->option('force') && !$this->shouldRunBySettings()) {
            $this->line('Backup skipped by schedule settings.');
            return self::SUCCESS;
        }

        $connection = config('database.default');
        $driver = (string) config("database.connections.{$connection}.driver");

        if ($driver === 'sqlite') {
            return $this->backupSqlite($connection);
        }

        if ($driver !== 'mysql') {
            $this->error("Database driver {$driver} is not supported for automated backups.");
            return self::FAILURE;
        }

        return $this->backupMysql($connection);
    }

    private function shouldRunBySettings(): bool
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('backup_settings')) {
            return true;
        }

        $settings = \App\Models\BackupSetting::query()->first();
        if (!$settings || !(bool) $settings->enabled) {
            return false;
        }

        $runTime = (string) ($settings->run_time ?? '02:30');
        $runDays = (array) ($settings->run_days ?? []);

        $now = now();
        $todayKey = strtolower($now->format('D'));
        if ($runDays !== [] && !in_array($todayKey, $runDays, true)) {
            return false;
        }

        return $now->format('H:i') === $runTime;
    }

    private function backupSqlite(string $connection): int
    {
        $databasePath = (string) config("database.connections.{$connection}.database");
        if ($databasePath === '' || !is_file($databasePath)) {
            $this->error('SQLite database file not found.');
            return self::FAILURE;
        }

        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        $filename = 'db-' . now()->format('Ymd-His') . '.sqlite';
        $targetPath = $backupDir . '/' . $filename;

        if (!copy($databasePath, $targetPath)) {
            $this->error('Failed to copy SQLite database file.');
            return self::FAILURE;
        }

        $this->line("Backup created: {$filename}");

        if ($this->option('prune')) {
            $this->pruneBackups();
        }

        return self::SUCCESS;
    }

    private function backupMysql(string $connection): int
    {
        $config = config("database.connections.{$connection}");
        $database = (string) ($config['database'] ?? '');
        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');
        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (string) ($config['port'] ?? '3306');
        $mysqldumpPath = trim((string) config('system_health.backup_mysqldump_path', ''));

        if ($database === '' || $username === '') {
            $this->error('MySQL configuration is incomplete.');
            return self::FAILURE;
        }

        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        $filename = 'db-' . now()->format('Ymd-His') . '.sql';
        $targetPath = $backupDir . '/' . $filename;

        $command = [
            $mysqldumpPath !== '' ? $mysqldumpPath : 'mysqldump',
            '--host=' . $host,
            '--port=' . $port,
            '--user=' . $username,
            '--result-file=' . $targetPath,
            '--databases',
            $database,
        ];

        if ($password !== '') {
            $command[] = '--password=' . $password;
        }

        $process = new Process($command);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            $errorOutput = $process->getErrorOutput() ?: $process->getOutput();
            $notFound = str_contains($errorOutput, 'not found')
                || str_contains($errorOutput, 'No such file or directory')
                || $process->getExitCode() === 127;

            if ($notFound) {
                $this->warn('mysqldump not available. Falling back to PHP export.');
                $fallback = $this->backupMysqlWithPdo($connection, $targetPath);
                if ($fallback === self::SUCCESS && $this->option('prune')) {
                    $this->pruneBackups();
                }
                return $fallback;
            }

            $this->error('mysqldump failed. Ensure mysqldump is installed (or set SYSTEM_HEALTH_BACKUP_MYSQLDUMP_PATH) and DB credentials are correct.');
            $this->error($errorOutput);
            return self::FAILURE;
        }

        $this->line("Backup created: {$filename}");

        if ($this->option('prune')) {
            $this->pruneBackups();
        }

        return self::SUCCESS;
    }

    private function backupMysqlWithPdo(string $connection, string $targetPath): int
    {
        try {
            $db = DB::connection($connection);
            $pdo = $db->getPdo();
        } catch (PDOException $exception) {
            $this->error('MySQL connection failed for PHP export.');
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $handle = fopen($targetPath, 'w');
        if ($handle === false) {
            $this->error('Failed to open backup file for writing.');
            return self::FAILURE;
        }

        fwrite($handle, "SET foreign_key_checks = 0;\n");
        fwrite($handle, "SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

        $tables = [];
        $tableStmt = $pdo->query("SHOW FULL TABLES WHERE Table_Type = 'BASE TABLE'");
        if ($tableStmt instanceof \PDOStatement) {
            while ($row = $tableStmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
        }

        foreach ($tables as $table) {
            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $createStmt ? $createStmt->fetch(PDO::FETCH_ASSOC) : null;
            $createSql = null;
            if (is_array($createRow)) {
                $createSql = $createRow['Create Table'] ?? $createRow['Create View'] ?? array_values($createRow)[1] ?? null;
            }
            if ($createSql === null) {
                continue;
            }

            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($handle, $createSql . ";\n\n");

            $dataStmt = $pdo->query("SELECT * FROM `{$table}`");
            if (!$dataStmt) {
                continue;
            }

            $dataStmt->setFetchMode(PDO::FETCH_ASSOC);
            $columns = null;
            $batch = [];
            while ($row = $dataStmt->fetch()) {
                if ($columns === null) {
                    $columns = array_keys($row);
                }
                $values = [];
                foreach ($columns as $column) {
                    $value = $row[$column];
                    if ($value === null) {
                        $values[] = 'NULL';
                    } elseif (is_bool($value)) {
                        $values[] = $value ? '1' : '0';
                    } elseif (is_numeric($value)) {
                        $values[] = (string) $value;
                    } else {
                        $values[] = $pdo->quote((string) $value);
                    }
                }
                $batch[] = '(' . implode(',', $values) . ')';
                if (count($batch) >= 500) {
                    $this->writeInsertBatch($handle, $table, $columns, $batch);
                    $batch = [];
                }
            }

            if ($batch !== [] && $columns !== null) {
                $this->writeInsertBatch($handle, $table, $columns, $batch);
            }

            fwrite($handle, "\n");
        }

        fwrite($handle, "SET foreign_key_checks = 1;\n");
        fclose($handle);

        $this->line('Backup created via PHP export.');
        return self::SUCCESS;
    }

    private function writeInsertBatch($handle, string $table, array $columns, array $batch): void
    {
        $columnList = '`' . implode('`,`', $columns) . '`';
        $sql = "INSERT INTO `{$table}` ({$columnList}) VALUES " . implode(',', $batch) . ";\n";
        fwrite($handle, $sql);
    }

    private function pruneBackups(): void
    {
        $keep = (int) config('system_health.backup_keep', 30);
        if (\Illuminate\Support\Facades\Schema::hasTable('backup_settings')) {
            $settings = \App\Models\BackupSetting::query()->first();
            if ($settings && (int) $settings->keep_count > 0) {
                $keep = (int) $settings->keep_count;
            }
        }
        $files = Storage::disk('local')->files('backups');
        $filesWithTime = [];

        foreach ($files as $file) {
            $filesWithTime[] = [
                'file' => $file,
                'time' => Storage::disk('local')->lastModified($file),
            ];
        }

        usort($filesWithTime, static function (array $a, array $b): int {
            return $b['time'] <=> $a['time'];
        });

        $filesToDelete = array_slice($filesWithTime, $keep);
        foreach ($filesToDelete as $entry) {
            Storage::disk('local')->delete($entry['file']);
        }
    }
}
