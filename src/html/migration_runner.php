<?php
// migration_runner.php
// Secure, idempotent migration runner for PalStory.
// Requires MIGRATE_TOKEN env and optional ?token= query for execution.

header('Content-Type: application/json');

function respond($status, $data = []) {
    http_response_code($status === 'ok' ? 200 : 400);
    echo json_encode(array_merge(['status' => $status], $data), JSON_PRETTY_PRINT);
    exit;
}

// Security: require token
$envToken = getenv('MIGRATE_TOKEN');
if (!$envToken) {
    respond('error', ['message' => 'MIGRATE_TOKEN not configured in environment']);
}
$reqToken = isset($_GET['token']) ? $_GET['token'] : '';
if (!hash_equals($envToken, $reqToken)) {
    respond('error', ['message' => 'Unauthorized']);
}

// Load DB config
$DB_SERVER = getenv('DB_SERVER') ?: 'localhost';
$DB_USERNAME = getenv('DB_USERNAME') ?: 'root';
$DB_PASSWORD = getenv('DB_PASSWORD') ?: '';
$DB_NAME = getenv('DB_NAME') ?: 'story';

// If config.php exists, include it to override
if (file_exists(__DIR__ . '/config.php')) {
    // config.php should define $DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_NAME
    include __DIR__ . '/config.php';
}

try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $DB_SERVER, $DB_NAME);
    $pdo = new PDO($dsn, $DB_USERNAME, $DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        // Use buffered queries to avoid 'Cannot execute queries while other unbuffered queries are active'
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        // Emulate prepares to further reduce server-side cursor usage
        PDO::ATTR_EMULATE_PREPARES => true
    ]);
} catch (Throwable $e) {
    respond('error', ['message' => 'DB connection failed', 'error' => $e->getMessage()]);
}

// Ensure schema_migrations exists
$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
  version VARCHAR(64) PRIMARY KEY,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  checksum VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

// Gather applied migrations
$applied = [];
$stmt = $pdo->query('SELECT version, checksum FROM schema_migrations');
$rows = $stmt->fetchAll();
foreach ($rows as $row) {
    $applied[$row['version']] = $row['checksum'];
}
// Ensure cursor is closed and statement released before executing more statements
if ($stmt) { $stmt->closeCursor(); }
$stmt = null;

// Scan migration files
$dir = __DIR__ . '/migrations';
if (!is_dir($dir)) {
    respond('error', ['message' => 'Migrations directory not found', 'dir' => $dir]);
}

$files = glob($dir . '/*.sql');
sort($files, SORT_NATURAL);

$executed = [];
$skipped = [];
$errors = [];

function run_sql_batch(PDO $pdo, $sql) {
    // Split by semicolon, ignore -- comments, keep basic robustness.
    $hasOwnTxn = (bool)preg_match('/\\b(START\\s+TRANSACTION|COMMIT|ROLLBACK)\\b/i', $sql);

    $lines = preg_split('/\r?\n/', $sql);
    $buffer = '';
    $statements = [];
    foreach ($lines as $line) {
        $trim = trim($line);
        // Skip single-line comments
        if ($trim === '' || str_starts_with($trim, '--')) {
            continue;
        }
        $buffer .= $line . "\n";
        if (substr(rtrim($line), -1) === ';') {
            $statements[] = $buffer;
            $buffer = '';
        }
    }
    if (trim($buffer) !== '') {
        $statements[] = $buffer;
    }

    // If the file manages its own transactions, execute as-is without wrapping
    if ($hasOwnTxn) {
        foreach ($statements as $stmt) {
            $trimmed = trim($stmt);
            if ($trimmed !== '') {
                // Use query+fetchAll for statements that may return a result set to fully buffer and close
                if (preg_match('/^(SELECT|SHOW|EXECUTE|DESCRIBE|EXPLAIN)\b/i', $trimmed)) {
                    $res = $pdo->query($stmt);
                    if ($res) { $res->fetchAll(); $res->closeCursor(); }
                } else {
                    $pdo->exec($stmt);
                }
            }
        }
        return;
    }

    // Otherwise try to wrap in a transaction; fall back if unsupported
    $useTxn = false;
    try {
        if ($pdo->beginTransaction()) {
            $useTxn = $pdo->inTransaction();
        }
    } catch (Throwable $e) {
        $useTxn = false; // fallback
    }

    try {
        foreach ($statements as $stmt) {
            $trimmed = trim($stmt);
            if ($trimmed !== '') {
                // Use query+fetchAll for statements that may return a result set to fully buffer and close
                if (preg_match('/^(SELECT|SHOW|EXECUTE|DESCRIBE|EXPLAIN)\b/i', $trimmed)) {
                    $res = $pdo->query($stmt);
                    if ($res) { $res->fetchAll(); $res->closeCursor(); }
                } else {
                    $pdo->exec($stmt);
                }
            }
        }
        if ($useTxn && $pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($useTxn && $pdo->inTransaction()) {
            try { $pdo->rollBack(); } catch (Throwable $_) {}
        }
        throw $e;
    }
}

foreach ($files as $path) {
    $filename = basename($path);
    $version = preg_replace('/\.sql$/', '', $filename);
    $sql = file_get_contents($path);
    $checksum = hash('sha256', $sql);

    if (isset($applied[$version])) {
        // If checksum differs, warn; otherwise skip.
        if ($applied[$version] !== $checksum) {
            $skipped[] = [
                'version' => $version,
                'reason' => 'already applied but checksum differs — manual intervention required'
            ];
        } else {
            $skipped[] = [
                'version' => $version,
                'reason' => 'already applied'
            ];
        }
        continue;
    }

    try {
        run_sql_batch($pdo, $sql);
        $ins = $pdo->prepare('INSERT INTO schema_migrations (version, checksum) VALUES (?, ?)');
        $ins->execute([$version, $checksum]);
        $executed[] = $version;
    } catch (Throwable $e) {
        $errors[] = [
            'version' => $version,
            'error' => $e->getMessage()
        ];
        break; // stop on first failure
    }
}

$status = empty($errors) ? 'ok' : 'error';
respond($status, [
    'executed' => $executed,
    'skipped' => $skipped,
    'errors' => $errors,
]);
