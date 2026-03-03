<?php

declare(strict_types=1);

function loadEnvFile(string $path): array
{
    $values = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        $value = trim($value, "\"'");
        $values[$key] = $value;
    }
    return $values;
}

$projectRoot = realpath(__DIR__ . '/..');
if ($projectRoot === false) {
    fwrite(STDERR, "Unable to resolve project root.\n");
    exit(1);
}

$env = loadEnvFile($projectRoot . '/.env');

$sqlitePath = $projectRoot . '/database/database.sqlite';
if (!is_file($sqlitePath)) {
    fwrite(STDERR, "SQLite file not found at {$sqlitePath}\n");
    exit(1);
}

$mysqlHost = $env['DB_HOST'] ?? '127.0.0.1';
$mysqlPort = (int) ($env['DB_PORT'] ?? 3306);
$mysqlDb = $env['DB_DATABASE'] ?? '';
$mysqlUser = $env['DB_USERNAME'] ?? 'root';
$mysqlPass = $env['DB_PASSWORD'] ?? '';

if ($mysqlDb === '' || str_contains($mysqlDb, '/')) {
    fwrite(STDERR, "Current DB_DATABASE is not a MySQL database name: {$mysqlDb}\n");
    exit(1);
}

try {
    $sqlite = new PDO('sqlite:' . $sqlitePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $mysql = new PDO("mysql:host={$mysqlHost};port={$mysqlPort};dbname={$mysqlDb};charset=utf8mb4", $mysqlUser, $mysqlPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "Connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

$tables = $sqlite
    ->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")
    ->fetchAll(PDO::FETCH_COLUMN);

if (!$tables) {
    fwrite(STDOUT, "No tables found in SQLite.\n");
    exit(0);
}

$mysql->exec('SET FOREIGN_KEY_CHECKS=0');

try {
    foreach ($tables as $table) {
        $mysql->exec("TRUNCATE TABLE `{$table}`");
    }

    foreach ($tables as $table) {
        $select = $sqlite->query("SELECT * FROM \"{$table}\"");
        $insertStmt = null;
        $rowCount = 0;

        while ($row = $select->fetch()) {
            if ($insertStmt === null) {
                $columns = array_keys($row);
                $columnSql = implode(', ', array_map(static fn (string $col): string => "`{$col}`", $columns));
                $paramSql = implode(', ', array_map(static fn (string $col): string => ':' . $col, $columns));
                $insertStmt = $mysql->prepare("INSERT INTO `{$table}` ({$columnSql}) VALUES ({$paramSql})");
            }

            foreach ($row as $key => $value) {
                $insertStmt->bindValue(':' . $key, $value);
            }
            $insertStmt->execute();
            $rowCount++;
        }

        fwrite(STDOUT, sprintf("Synced %-28s %6d rows\n", $table, $rowCount));
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Sync failed: " . $e->getMessage() . "\n");
    $mysql->exec('SET FOREIGN_KEY_CHECKS=1');
    exit(1);
}

$mysql->exec('SET FOREIGN_KEY_CHECKS=1');
fwrite(STDOUT, "SQLite -> MySQL sync completed successfully.\n");
