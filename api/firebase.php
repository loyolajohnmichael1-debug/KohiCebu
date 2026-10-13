
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Kreait\Firebase\Factory;

$serviceAccountPath = getenv("FIREBASE_CREDENTIALS_PATH");

if (
    $serviceAccountPath === false ||
    $serviceAccountPath === "" ||
    !is_file($serviceAccountPath) ||
    !is_readable($serviceAccountPath)
) {
    error_log("KohiCebu Firebase service account is not configured.");

    throw new RuntimeException(
        "Firebase service account configuration is unavailable."
    );
}

$factory = (new Factory)
    ->withServiceAccount($serviceAccountPath);

$messaging = $factory->createMessaging();
