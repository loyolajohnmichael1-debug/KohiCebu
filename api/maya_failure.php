<?php

header("Content-Type: application/json");

echo json_encode([
    "success" => false,
    "message" => "Payment was not completed."
]);

?>