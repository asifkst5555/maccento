<?php
declare(strict_types=1);

// Usage:
//   php tools/sqlite_to_mysql.php [--sqlite=path/to.sqlite]
// Relies on .env for MySQL connection details.

function parseArgs(array $argv): array {
    $out = [
        'sqlite' => 'database/database.sqlite',
    ];
    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--sqlite=')) {
            $out['sqlite'] = substr($arg, strlen('--sqlite='));
        }
    }
    return $out;
}

function envOrFail(array $env, string $key): string {
    if (!array_key_exists($key, $env)) {
        fwrite(STDERR, "Missing {$key} in .env\n");
        exit(1);
    }
    return (string)$env[$key];
}

function qIdent(string $name): string {
    return '`' . str_replace('`', '``', $name) . '`';
}

$args = parseArgs(array_slice($argv, 1));
$sqlitePath = $args['sqlite'];

if (!file_exists($sqlitePath)) {
    fwrite(STDERR, "SQLite file not found: {$sqlitePath}\n");
    exit(1);
}

function loadEnv(string $path): array {
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return [];
    }
    $env = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        if ($val !== '' && ($val[0] === '"' || $val[0] === "'")) {
            $quote = $val[0];
            if (str_ends_with($val, $quote)) {
                $val = substr($val, 1, -1);
            } else {
                $val = substr($val, 1);
            }
        }
        $env[$key] = $val;
    }
    return $env;
}

$env = loadEnv(__DIR__ . '/../.env');
if (!$env) {
    fwrite(STDERR, "Failed to read .env\n");
    exit(1);
}

$dbHost = envOrFail($env, 'DB_HOST');
$dbPort = envOrFail($env, 'DB_PORT');
$dbName = envOrFail($env, 'DB_DATABASE');
$dbUser = envOrFail($env, 'DB_USERNAME');
$dbPass = (string)($env['DB_PASSWORD'] ?? '');

$mysqlDsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
$sqliteDsn = "sqlite:" . $sqlitePath;

try {
    $mysql = new PDO($mysqlDsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "MySQL connection failed: {$e->getMessage()}\n");
    exit(1);
}

try {
    $sqlite = new PDO($sqliteDsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "SQLite connection failed: {$e->getMessage()}\n");
    exit(1);
}

$tablesStmt = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
$tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

if (!$tables) {
    fwrite(STDOUT, "No tables found in SQLite database.\n");
    exit(0);
}

$mysql->exec("SET FOREIGN_KEY_CHECKS=0");

$totalRows = 0;
foreach ($tables as $table) {
    $check = $mysql->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $check->execute([$table]);
    if ($check->fetchColumn() === false) {
        fwrite(STDOUT, "Skip (missing in MySQL): {$table}\n");
        continue;
    }

    $mysql->exec("TRUNCATE TABLE " . qIdent($table));

    $colsStmt = $sqlite->query("PRAGMA table_info(\"{$table}\")");
    $cols = [];
    while ($row = $colsStmt->fetch()) {
        $cols[] = $row['name'];
    }

    if (!$cols) {
        fwrite(STDOUT, "Skip (no columns): {$table}\n");
        continue;
    }

    $colList = implode(',', array_map('qIdent', $cols));
    $placeholders = implode(',', array_fill(0, count($cols), '?'));
    $insertSql = "INSERT INTO " . qIdent($table) . " ({$colList}) VALUES ({$placeholders})";
    $insertStmt = $mysql->prepare($insertSql);

    $selectStmt = $sqlite->query("SELECT * FROM \"{$table}\"");
    $rowCount = 0;

    $mysql->beginTransaction();
    while ($row = $selectStmt->fetch()) {
        $values = [];
        foreach ($cols as $c) {
            $values[] = $row[$c] ?? null;
        }
        $insertStmt->execute($values);
        $rowCount++;
    }
    $mysql->commit();

    $totalRows += $rowCount;
    fwrite(STDOUT, "Imported {$rowCount} rows into {$table}\n");
}

$mysql->exec("SET FOREIGN_KEY_CHECKS=1");

fwrite(STDOUT, "Done. Total rows imported: {$totalRows}\n");
