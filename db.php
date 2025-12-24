<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$envPath = __DIR__ . "/.env";
$env = file_exists($envPath) ? (parse_ini_file($envPath, false, INI_SCANNER_RAW) ?: []) : [];

$host = $env['DB_HOST'] ?? 'localhost';
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASS'] ?? '';
$db   = $env['DB_NAME'] ?? '';

if ($db === '') {
    throw new Exception("DB_NAME missing in .env");
}

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    throw new Exception("DB connect failed: " . mysqli_connect_error());
}
