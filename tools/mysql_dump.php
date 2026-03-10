<?php
declare(strict_types=1);

// Usage:
//   php tools/mysql_dump.php [--out=/path/to/backup.sql]
// Uses .env for MySQL connection details and dumps schema + data.

function parseArgs(array $argv): array {
    $out = [
        'out' => '',
    ];
    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--out=')) {
            $out['out'] = substr($arg, strlen('--out='));
        }
    }
    return $out;
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

function sqlValue($val, PDO $pdo): string {
    if ($val === null) {
        return 'NULL';
    }
    return $pdo->quote((string)$val);
}

$args = parseArgs(array_slice($argv, 1));

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

$outPath = $args['out'];
if ($outPath === '') {
    $ts = date('Ymd-His');
    $outPath = __DIR__ . "/../{$dbName}-backup-{$ts}.sql";
}

$fh = fopen($outPath, 'wb');
if ($fh === false) {
    fwrite(STDERR, "Failed to open output file: {$outPath}\n");
    exit(1);
}

fwrite($fh, "-- Backup of {$dbName}\n");
fwrite($fh, "-- Generated at " . date('c') . "\n\n");
fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n\n");

$tablesStmt = $mysql->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type='BASE TABLE' ORDER BY table_name");
$tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    $createStmt = $mysql->query("SHOW CREATE TABLE " . qIdent($table));
    $row = $createStmt->fetch(PDO::FETCH_NUM);
    $createSql = $row[1] ?? '';
    if ($createSql === '') {
        continue;
    }

    fwrite($fh, "DROP TABLE IF EXISTS " . qIdent($table) . ";\n");
    fwrite($fh, $createSql . ";\n\n");

    $colsStmt = $mysql->query("SHOW COLUMNS FROM " . qIdent($table));
    $cols = [];
    while ($c = $colsStmt->fetch()) {
        $cols[] = $c['Field'];
    }
    if (!$cols) {
        fwrite($fh, "\n");
        continue;
    }

    $dataStmt = $mysql->query("SELECT * FROM " . qIdent($table));
    $colList = implode(',', array_map('qIdent', $cols));

    while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
        $vals = [];
        foreach ($cols as $c) {
            $vals[] = sqlValue($row[$c] ?? null, $mysql);
        }
        $valsSql = implode(',', $vals);
        fwrite($fh, "INSERT INTO " . qIdent($table) . " ({$colList}) VALUES ({$valsSql});\n");
    }
    fwrite($fh, "\n");
}

fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
fclose($fh);

fwrite(STDOUT, "Backup written to {$outPath}\n");
