<?php
// schema_dumper.php
// Dumps the complete database schema to the browser
// Requires MIGRATE_TOKEN env and ?token= query parameter (same as migration_runner.php)

// If config.php exists, include it to override
foreach ([__DIR__ . '/game/config.php', __DIR__ . '/config.php'] as $cfg) {
    if (file_exists($cfg)) {
        include $cfg;
        break;
    }
}

// Load DB config
$DB_SERVER = getenv('DB_SERVER') ?: 'localhost';
$DB_USERNAME = getenv('DB_USERNAME') ?: 'root';
$DB_PASSWORD = getenv('DB_PASSWORD') ?: '';
$DB_NAME = getenv('DB_NAME') ?: 'story';

// Security: require token (same as migration_runner.php)
$envToken = getenv('MIGRATE_TOKEN');
if (!$envToken) {
    http_response_code(403);
    die('Error: MIGRATE_TOKEN not configured in environment or config.php');
}
$reqToken = isset($_GET['token']) ? $_GET['token'] : '';
if (!hash_equals($envToken, $reqToken)) {
    http_response_code(403);
    die('Error: Unauthorized - invalid token');
}

// Check if data should be included
$includeData = isset($_GET['data']) && $_GET['data'] === '1';

try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $DB_SERVER, $DB_NAME);
    $pdo = new PDO($dsn, $DB_USERNAME, $DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    die('Error: DB connection failed - ' . htmlspecialchars($e->getMessage()));
}

// Set content type to plain text for easy copying
header('Content-Type: text/plain; charset=utf-8');

echo "-- ============================================================================\n";
echo "-- Database Schema Dump for: {$DB_NAME}\n";
echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
echo "-- ============================================================================\n\n";

// Get all tables
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    echo "-- ============================================================================\n";
    echo "-- Table: {$table}\n";
    echo "-- ============================================================================\n\n";
    
    // Get CREATE TABLE statement
    $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
    echo $createStmt['Create Table'] . ";\n\n";
    
    // Get table row count
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM `{$table}`")->fetch();
    echo "-- Row count: {$count['cnt']}\n\n";
    
    // Get column information
    echo "-- Column Details:\n";
    $columns = $pdo->query("SHOW FULL COLUMNS FROM `{$table}`")->fetchAll();
    foreach ($columns as $col) {
        $comment = $col['Comment'] ? " -- {$col['Comment']}" : '';
        echo "-- {$col['Field']}: {$col['Type']} " . 
             ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . 
             ($col['Default'] !== null ? " DEFAULT '{$col['Default']}'" : '') .
             ($col['Extra'] ? " {$col['Extra']}" : '') .
             $comment . "\n";
    }
    echo "\n";
    
    // Get indexes
    $indexes = $pdo->query("SHOW INDEXES FROM `{$table}`")->fetchAll();
    if (!empty($indexes)) {
        echo "-- Indexes:\n";
        $indexGroups = [];
        foreach ($indexes as $idx) {
            $indexGroups[$idx['Key_name']][] = $idx;
        }
        foreach ($indexGroups as $keyName => $cols) {
            $colNames = array_map(function($c) { return $c['Column_name']; }, $cols);
            $type = $cols[0]['Key_name'] === 'PRIMARY' ? 'PRIMARY KEY' : 
                    ($cols[0]['Non_unique'] == 0 ? 'UNIQUE' : 'INDEX');
            echo "-- {$type}: {$keyName} (" . implode(', ', $colNames) . ")\n";
        }
        echo "\n";
    }
    
    // Include data if requested
    if ($includeData && $count['cnt'] > 0) {
        echo "-- Data:\n";
        echo "-- ============================================================================\n\n";
        
        // Get all rows
        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll();
        
        if (!empty($rows)) {
            // Get column names
            $columnNames = array_keys($rows[0]);
            $escapedColumns = array_map(function($col) { return "`{$col}`"; }, $columnNames);
            
            echo "INSERT INTO `{$table}` (" . implode(', ', $escapedColumns) . ") VALUES\n";
            
            $rowCount = count($rows);
            foreach ($rows as $index => $row) {
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        // Escape single quotes and wrap in quotes
                        $escaped = str_replace("'", "''", $value);
                        $values[] = "'" . $escaped . "'";
                    }
                }
                
                $isLast = ($index === $rowCount - 1);
                echo "(" . implode(', ', $values) . ")" . ($isLast ? ";\n" : ",\n");
            }
            echo "\n";
        }
    }
    
    echo "\n";
}

// Get database-level information
echo "-- ============================================================================\n";
echo "-- Database Information\n";
echo "-- ============================================================================\n\n";

$dbInfo = $pdo->query("SELECT 
    DEFAULT_CHARACTER_SET_NAME as charset,
    DEFAULT_COLLATION_NAME as collation
    FROM information_schema.SCHEMATA 
    WHERE SCHEMA_NAME = '{$DB_NAME}'")->fetch();

echo "-- Character Set: {$dbInfo['charset']}\n";
echo "-- Collation: {$dbInfo['collation']}\n\n";

// Get table sizes
echo "-- Table Sizes:\n";
$sizes = $pdo->query("SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
    FROM information_schema.TABLES 
    WHERE table_schema = '{$DB_NAME}'
    ORDER BY (data_length + index_length) DESC")->fetchAll();

foreach ($sizes as $size) {
    echo "-- {$size['table_name']}: {$size['size_mb']} MB\n";
}

echo "\n-- ============================================================================\n";
echo "-- End of Schema Dump\n";
echo "-- ============================================================================\n";
