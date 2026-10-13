<?php

header("Content-Type: application/json");
require_once "db.php";
require_once "auth.php";

$user = requireAuth($conn);
requireRole($user, ["ADMIN"]);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "POST method required."]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid JSON request body."]);
    exit;
}

$unitName = trim($input["unit_name"] ?? "");
$unitCode = strtoupper(trim($input["unit_code"] ?? ""));
$unitType = strtoupper(trim($input["unit_type"] ?? ""));

if ($unitName === "" || strlen($unitName) > 50) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Unit name is required and must be 50 characters or fewer."]);
    exit;
}

if (!preg_match("/^[A-Z0-9][A-Z0-9._\\/-]{0,19}$/", $unitCode)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Unit code must be 1-20 characters and contain only letters, numbers, dots, underscores, slashes, or hyphens."]);
    exit;
}

if (!in_array($unitType, ["WEIGHT", "VOLUME", "COUNT"], true)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Select a valid unit type."]);
    exit;
}

$result = pg_query_params(
    $conn,
    "INSERT INTO inventory_units (unit_name, unit_code, unit_type) VALUES ($1, $2, $3) ON CONFLICT (unit_code) DO NOTHING RETURNING unit_id, unit_name, unit_code, unit_type",
    [$unitName, $unitCode, $unitType]
);

if (!$result) {
    error_log("Unable to create inventory unit: " . pg_last_error($conn));
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Unable to create inventory unit."]);
    exit;
}

if (pg_num_rows($result) === 0) {
    http_response_code(409);
    echo json_encode(["success" => false, "message" => "That unit code is already configured."]);
    exit;
}

$row = pg_fetch_assoc($result);
$unit = [
    "unit_id" => (int) $row["unit_id"],
    "unit_name" => $row["unit_name"],
    "unit_code" => $row["unit_code"],
    "unit_type" => $row["unit_type"]
];

echo json_encode([
    "success" => true,
    "message" => "Inventory unit created successfully.",
    "unit" => $unit
]);