<?php
session_start();
require_once "database.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["user_id"], $_SESSION["user_type"])) {
    http_response_code(401);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$meeting_id = (int)($data["id"] ?? 0);
$status     = $data["status"] ?? "";

$allowed = ["Scheduled", "Completed", "Cancelled"];
if (!$meeting_id || !in_array($status, $allowed)) {
    http_response_code(400);
    exit();
}

$user_id   = $_SESSION["user_id"];
$user_type = $_SESSION["user_type"];

if ($user_type === "Startup") {
    $sql = "
        UPDATE Meeting m
        JOIN Startup_Profile sp ON m.Startup_id = sp.Startup_id
        SET m.Status = ?
        WHERE m.Meeting_id = ? AND sp.User_id = ?
    ";
} else {
    $sql = "
        UPDATE Meeting m
        JOIN Investor_Profile ip ON m.Investor_id = ip.Investor_id
        SET m.Status = ?
        WHERE m.Meeting_id = ? AND ip.User_id = ?
    ";
}

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sii", $status, $meeting_id, $user_id);
if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Update failed"]);
    exit();
}

if (mysqli_stmt_affected_rows($stmt) < 1) {
    http_response_code(404);
    echo json_encode(["success" => false, "error" => "Meeting not found or not authorized"]);
    exit();
}

echo json_encode(["success" => true]);
