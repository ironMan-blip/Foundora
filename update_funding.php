<?php
session_start();
header("Content-Type: application/json");
require_once "database.php";

if (!isset($_SESSION["user_id"], $_SESSION["user_type"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true) ?: [];

$action     = trim($data["action"] ?? "update");
$funding_id = (int)($data["id"] ?? 0);

if (!$funding_id) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Missing funding id"]);
    exit();
}

$user_id   = (int)$_SESSION["user_id"];
$user_type = $_SESSION["user_type"];

if ($action === "delete") {

    if ($user_type === "Startup") {
        $sql = "
            DELETE f FROM Funding f
            JOIN Startup_Profile sp ON f.Startup_id = sp.Startup_id
            WHERE f.Funding_id = ? AND sp.User_id = ?
        ";
    } else {
        $sql = "
            DELETE f FROM Funding f
            JOIN Investor_Profile ip ON f.Investor_id = ip.Investor_id
            WHERE f.Funding_id = ? AND ip.User_id = ?
        ";
    }

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $funding_id, $user_id);
    mysqli_stmt_execute($stmt);

    if (mysqli_affected_rows($conn) === 0) {
        http_response_code(404);
        echo json_encode(["success" => false, "error" => "Deal not found or not authorized"]);
        exit();
    }

    echo json_encode(["success" => true]);
    exit();
}

// default: update
$stage  = trim($data["stage"] ?? ($data["status"] ?? ""));
$amount = $data["amount"] ?? null;

if ($stage === "" || $amount === null || !is_numeric($amount)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Invalid update payload"]);
    exit();
}

$amount_f = (float)$amount;

if ($user_type === "Startup") {
    $sql = "
        UPDATE Funding f
        JOIN Startup_Profile sp ON f.Startup_id = sp.Startup_id
        SET f.Status = ?, f.Amount = ?
        WHERE f.Funding_id = ? AND sp.User_id = ?
    ";
} else {
    $sql = "
        UPDATE Funding f
        JOIN Investor_Profile ip ON f.Investor_id = ip.Investor_id
        SET f.Status = ?, f.Amount = ?
        WHERE f.Funding_id = ? AND ip.User_id = ?
    ";
}

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Update prepare failed"]);
    exit();
}

mysqli_stmt_bind_param($stmt, "sdii", $stage, $amount_f, $funding_id, $user_id);
mysqli_stmt_execute($stmt);

if (mysqli_affected_rows($conn) === 0) {
    // Could be "no change" or "not found". Check existence/authorization quickly.
    http_response_code(404);
    echo json_encode(["success" => false, "error" => "Deal not found or not authorized"]);
    exit();
}

echo json_encode(["success" => true]);
?>
