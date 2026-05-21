<?php
/**
 * Database connection using environment variables.
 * Works on Render with DB_* environment variables set.
 */

// Load .env in local development if present
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    if ($env) {
        foreach ($env as $key => $value) {
            putenv("$key=$value");
        }
    }
}

$db_host = getenv('DB_HOST');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_name = getenv('DB_NAME');
$db_port = getenv('DB_PORT');

if (!$db_host || !$db_user || !$db_name || !$db_port) {
    die('Missing database environment variables. Configure DB_HOST, DB_USER, DB_PASS, DB_NAME and DB_PORT.');
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) {
    die('Conexión fallida: ' . $conn->connect_error);
}
$conn->set_charset('utf8');

$GLOBALS['db_connection'] = $conn;
?>