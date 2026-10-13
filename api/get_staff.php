<?php

header("Content-Type: application/json");

require_once "db.php";
require_once "auth.php";

$user = requireAuth($conn);
requireRole($user, ["ADMIN"]);


/* =========================================================
   REQUEST METHOD
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only GET requests are allowed."
    ]);

    exit;
}


/* =========================================================
   GET STAFF ACCOUNTS
========================================================= */

$query = "
    SELECT
        user_id,
        full_name,
        email,
        phone,
        address,
        role,
        is_active,
        created_at
    FROM users
    WHERE role = 'STAFF'
    ORDER BY
        is_active DESC,
        full_name ASC
";


$result = pg_query(
    $conn,
    $query
);


if (!$result) {

    error_log(
        "Failed to retrieve staff accounts."
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve staff accounts."
    ]);

    exit;
}


/* =========================================================
   BUILD STAFF LIST
========================================================= */

$staff = [];


while ($row = pg_fetch_assoc($result)) {

    $staff[] = [
        "user_id" =>
            (int) $row["user_id"],

        "full_name" =>
            $row["full_name"],

        "email" =>
            $row["email"],

        "phone" =>
            $row["phone"],

        "address" =>
            $row["address"],

        "role" =>
            $row["role"],

        "is_active" =>
            ($row["is_active"] === "t"),

        "created_at" =>
            $row["created_at"]
    ];
}


/* =========================================================
   RESPONSE
========================================================= */

echo json_encode([
    "success" => true,
    "message" => "Staff accounts retrieved successfully.",
    "count" => count($staff),
    "staff" => $staff
]);

?>