<?php

header("Content-Type: application/json");

echo json_encode([
    "success" => true,
    "message" => "Payment checkout completed. Payment verification is handled by the webhook."
]);

?>