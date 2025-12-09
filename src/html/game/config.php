<?php
// Configuration
$IMAGE_BASE_URL = "https://palplanner.com/story/uploads/";
$NEWS_URL = "https://palplanner.com/story/news/index.php";
$MODEL3D_BASE_PATH = "https://palplanner.com/story/uploads/";

// Helper to read environment variables reliably in Apache/PHP
function env_val($key, $default = null) {
    $v = getenv($key);
    if ($v === false) {
        $v = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }
    return ($v === null || $v === '') ? $default : $v;
}

// Read DB configuration from environment
$DB_SERVER = env_val('DB_SERVER');
$DB_USERNAME = env_val('DB_USERNAME');
$DB_PASSWORD = env_val('DB_PASSWORD');
$DB_NAME = env_val('DB_NAME');
// Optional port (some platforms expose MySQL on a non-standard port)
$DB_PORT = env_val('DB_PORT');
if ($DB_PORT) {
    // mysqli_connect accepts host in the form host:port
    $DB_SERVER = $DB_SERVER . ':' . $DB_PORT;
}

if (!$DB_SERVER || !$DB_USERNAME || !$DB_NAME) {
    http_response_code(500);
    echo "<h1>Configuration error</h1>";
    echo "<p>Missing database environment variables. Please set: <code>DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME</code>.</p>";
    echo "<p>For local testing with Docker: run the container with <code>-e DB_SERVER=... -e DB_USERNAME=... -e DB_PASSWORD=... -e DB_NAME=...</code>.</p>";
    exit;
}

$db = @mysqli_connect($DB_SERVER, $DB_USERNAME, $DB_PASSWORD, $DB_NAME);
if (!$db) {
    http_response_code(500);
    echo "<h1>Database connection failed</h1>";
    echo "<p>Could not connect to MySQL at <code>" . htmlspecialchars($DB_SERVER) . "</code> for database <code>" . htmlspecialchars($DB_NAME) . "</code>.</p>";
    exit;
}

function clean($str) {
    return htmlspecialchars($str, ENT_QUOTES);
}

function strip($str) {
    return strip_tags($str);
}

// Base path helper for URL generation (e.g., "/story" for private servers, "" for public)
// Set via putenv("BASE_PATH=/story") in config or environment
function base_path($path = '') {
    $base = env_val('BASE_PATH', '');
    // Ensure base starts with / if not empty
    if ($base && $base[0] !== '/') {
        $base = '/' . $base;
    }
    // Remove trailing slash from base
    $base = rtrim($base, '/');
    // Ensure path starts with / if not empty
    if ($path && $path[0] !== '/') {
        $path = '/' . $path;
    }
    return $base . $path;
}

function getImageUrl($imagePath) {
    global $IMAGE_BASE_URL;
    // Remove leading slashes and "uploads/" prefix if present
    $imagePath = ltrim($imagePath, '/');
    $imagePath = preg_replace('#^(story/)?uploads/#', '', $imagePath);
    return $IMAGE_BASE_URL . $imagePath;
}

function admin_game() {
    return env_val("ADMIN_GAME", "admin_game");
}

function super_admin_game() {
    return env_val("SUPER_ADMIN_GAME", "super_admin_game");
}

// Hashed versions for secure login comparison (SHA-256)
function admin_game_hash() {
    return hash('sha256', admin_game());
}

function super_admin_game_hash() {
    return hash('sha256', super_admin_game());
}

/**
 * Optional URL to a JSON file providing header items to render in the header center.
 * Expected JSON shape: { "items": [ "<a href='...'>Item</a>", "<span>News</span>" ] }
 */
function header_items_url() {
    global $NEWS_URL;
    return isset($NEWS_URL) ? trim($NEWS_URL) : "";
}

// --- Rate limiting config (combat) ---
// Allow overriding via environment variables; fallback to sensible defaults
if (!defined('COMBAT_RL_WINDOW_SEC')) {
    $w = isset($_ENV['COMBAT_RL_WINDOW_SEC']) ? intval($_ENV['COMBAT_RL_WINDOW_SEC']) : 1;
    if ($w <= 0) { $w = 1; }
    define('COMBAT_RL_WINDOW_SEC', $w);
}
if (!defined('COMBAT_RL_MAX_ACTIONS')) {
    $m = isset($_ENV['COMBAT_RL_MAX_ACTIONS']) ? intval($_ENV['COMBAT_RL_MAX_ACTIONS']) : 3;
    if ($m <= 0) { $m = 3; }
    define('COMBAT_RL_MAX_ACTIONS', $m);
}

// --- CSRF Protection ---
// Start session for CSRF token management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate or retrieve the CSRF token for the current session.
 * @return string The CSRF token
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token against the session token.
 * @param string $token The token to validate
 * @return bool True if valid, false otherwise
 */
function csrf_validate($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token from request (checks both POST and header).
 * @return string|null The token or null if not found
 */
function csrf_get_from_request() {
    // Check POST data
    if (isset($_POST['csrf_token'])) {
        return $_POST['csrf_token'];
    }
    // Check GET data (for AJAX)
    if (isset($_GET['csrf_token'])) {
        return $_GET['csrf_token'];
    }
    // Check X-CSRF-Token header
    $headers = getallheaders();
    if (isset($headers['X-CSRF-Token'])) {
        return $headers['X-CSRF-Token'];
    }
    if (isset($headers['X-Csrf-Token'])) {
        return $headers['X-Csrf-Token'];
    }
    return null;
}

/**
 * Validate CSRF token from request. Returns true if valid or if CSRF is disabled.
 * @return bool True if valid
 */
function csrf_check() {
    // Allow disabling CSRF for development via environment variable
    if (env_val('DISABLE_CSRF', false)) {
        return true;
    }
    $token = csrf_get_from_request();
    return csrf_validate($token);
}

/**
 * Fetch header items from configured URL. Returns an array of HTML strings.
 */
function fetch_header_items() {
    $url = header_items_url();
    if (!$url) return [];

    // Prefer cURL for better control
    $ch = curl_init($url);
    if (!$ch) return [];
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PalStory-Header/1.0');
    $resp = curl_exec($ch);
    if (curl_errno($ch)) { curl_close($ch); return []; }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code < 200 || $code >= 300) return [];

    $json = json_decode($resp, true);
    if (!is_array($json)) return [];
    $items = $json['items'] ?? [];
    return is_array($items) ? $items : [];
}

?>