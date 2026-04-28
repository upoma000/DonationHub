<?php
function loadEnvFile($path) {
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key === '' || getenv($key) !== false) {
            continue;
        }

        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

// Only load local .env for local development.
// On Render we should rely on real environment variables.
$isRender = (getenv('RENDER') !== false) || (getenv('RENDER_SERVICE_ID') !== false);
if (!$isRender) {
    loadEnvFile(__DIR__ . DIRECTORY_SEPARATOR . '.env');
}

function envValue(...$keys) {
    foreach ($keys as $key) {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }
    }
    return false;
}

$databaseUrl = envValue('DATABASE_URL', 'MYSQL_URL', 'JAWSDB_URL');

$host = "localhost";
$port = 3306;
$user = "root";
$password = "";
$database = "mydatabase";

if ($databaseUrl) {
    $parts = parse_url($databaseUrl);
    if ($parts !== false) {
        $host = $parts['host'] ?? $host;
        $port = isset($parts['port']) ? (int) $parts['port'] : $port;
        $user = isset($parts['user']) ? urldecode($parts['user']) : $user;
        $password = isset($parts['pass']) ? urldecode($parts['pass']) : $password;
        if (!empty($parts['path']) && $parts['path'] !== '/') {
            $database = ltrim($parts['path'], '/');
        }
    }
}

$host = envValue('DB_HOST', 'MYSQLHOST') ?: $host;
$port = (int) (envValue('DB_PORT', 'MYSQLPORT') ?: $port);
$user = envValue('DB_USER', 'MYSQLUSER') ?: $user;
$password = envValue('DB_PASSWORD', 'MYSQLPASSWORD');
$database = envValue('DB_NAME', 'MYSQLDATABASE') ?: $database;

if ($password === false) {
    $password = "";
}

// Allow DB_HOST values like "hostname:3306".
if (strpos($host, ':') !== false) {
    [$parsedHost, $parsedPort] = explode(':', $host, 2);
    if ($parsedHost !== '') {
        $host = $parsedHost;
    }
    if (is_numeric($parsedPort)) {
        $port = (int) $parsedPort;
    }
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $password, $database, $port);
    $conn->set_charset("utf8");
} catch (Throwable $e) {
    $details = [];
    if ($databaseUrl) {
        $details[] = 'DATABASE_URL';
    }
    if (envValue('DB_HOST', 'MYSQLHOST')) {
        $details[] = 'DB_HOST';
    }
    if (envValue('DB_NAME', 'MYSQLDATABASE')) {
        $details[] = 'DB_NAME';
    }
    if (envValue('DB_USER', 'MYSQLUSER')) {
        $details[] = 'DB_USER';
    }

    $checked = empty($details) ? 'no database env vars detected' : ('checked: ' . implode(', ', $details));
    die("Database connection failed ($checked).");
}
?>