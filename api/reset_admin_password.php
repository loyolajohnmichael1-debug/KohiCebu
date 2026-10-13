
<?php

header("Content-Type: application/json");
http_response_code(410);

echo json_encode([
    "success" => false,
    "message" => "This password reset endpoint has been disabled."
]);

?>
