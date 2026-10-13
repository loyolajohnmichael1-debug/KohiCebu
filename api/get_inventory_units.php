<?php

header("Content-Type: application/json");

require_once "db.php";
require_once "auth.php";

$user = requireAuth($conn);

requireRole($user, ["ADMIN", "STAFF"]);

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "GET method required."
    ]);

    exit;
}

$query = "
    SELECT
        unit_id,
        unit_name,
        unit_code,
        unit_type
    FROM inventory_units
    ORDER BY
        unit_type,
        unit_name
";

$result = pg_query($conn, $query);

if (!$result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve inventory units."
    ]);

    exit;
}

$units = [];

while ($row = pg_fetch_assoc($result)) {

    $units[] = [
        "unit_id" =>
            (int) $row["unit_id"],

        "unit_name" =>
            $row["unit_name"],

        "unit_code" =>
            $row["unit_code"],

        "unit_type" =>
            $row["unit_type"]
    ];
}

echo json_encode([
    "success" => true,
    "message" => "Inventory units retrieved successfully.",
    "count" => count($units),
    "units" => $units
]);