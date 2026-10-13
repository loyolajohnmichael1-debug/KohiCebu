
<?php

header("Content-Type: application/json");

// Load local .env settings when available.
// Hosting environment variables take precedence.
$envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . ".env";

if (is_file($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines !== false) {
        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === "" || str_starts_with($line, "#") || !str_contains($line, "=")) {
                continue;
            }

            [$key, $value] = explode("=", $line, 2);
            $key = trim($key);
            $value = trim($value);

            if ($key === "") {
                continue;
            }

            if (
                strlen($value) >= 2 &&
                (
                    ($value[0] === '"' && str_ends_with($value, '"')) ||
                    ($value[0] === "'" && str_ends_with($value, "'"))
                )
            ) {
                $value = substr($value, 1, -1);
            }

            if (getenv($key) === false) {
                putenv($key . "=" . $value);
            }
        }
    }
}

$host = getenv("DB_HOST") ?: "localhost";
$port = getenv("DB_PORT") ?: "5432";
$dbname = getenv("DB_NAME") ?: "kohicebu";
$user = getenv("DB_USER") ?: "postgres";
$password = getenv("DB_PASSWORD");

if ($password === false || $password === "") {
    error_log("KohiCebu database password is not configured.");
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Database configuration is incomplete."
    ]);
    exit;
}

$connString = sprintf(
    "host=%s port=%s dbname=%s user=%s password=%s",
    $host,
    $port,
    $dbname,
    $user,
    $password
);

$conn = pg_connect($connString);

if (!$conn) {
    error_log("KohiCebu database connection failed.");
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);
    exit;
}

?>
