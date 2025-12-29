<?php
session_start();
header("Content-Type: application/json");
require_once "database.php";

if (!isset($_SESSION["user_id"], $_SESSION["user_type"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit();
}

// Rule: Only Investors can add new funding deals.
// Keep this check server-side so a Startup cannot bypass it via DevTools.
$ut = strtolower(trim((string)$_SESSION["user_type"]));
if ($ut !== "investor") {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Only investors can add funding deals"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true) ?: [];

$counterpart = trim($data["counterpart"] ?? "");
$stage       = trim($data["stage"] ?? "Intro");
$amount      = $data["amount"] ?? 0;

if ($counterpart === "" || !is_numeric($amount) || (float)$amount <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Invalid input"]);
    exit();
}

$user_id   = (int)$_SESSION["user_id"];
// Normalize (some parts of the project use "Investor"/"Startup" while others use lowercase)
$user_type = strtolower(trim((string)$_SESSION["user_type"]));

$startup_id = null;
$investor_id = null;

if ($user_type === "investor") {

    // Current user -> investor_id
    $q = mysqli_prepare($conn, "SELECT Investor_id FROM Investor_Profile WHERE User_id = ?");
    mysqli_stmt_bind_param($q, "i", $user_id);
    mysqli_stmt_execute($q);
    $r = mysqli_stmt_get_result($q);
    $row = mysqli_fetch_assoc($r);
    $investor_id = $row["Investor_id"] ?? null;

    // Counterpart name -> startup_id
    $q2 = mysqli_prepare($conn, "SELECT Startup_id FROM Startup_Profile WHERE Startup_name = ?");
    mysqli_stmt_bind_param($q2, "s", $counterpart);
    mysqli_stmt_execute($q2);
    $r2 = mysqli_stmt_get_result($q2);
    $row2 = mysqli_fetch_assoc($r2);
    $startup_id = $row2["Startup_id"] ?? null;

} else {

    // Current user -> startup_id
    $q = mysqli_prepare($conn, "SELECT Startup_id FROM Startup_Profile WHERE User_id = ?");
    mysqli_stmt_bind_param($q, "i", $user_id);
    mysqli_stmt_execute($q);
    $r = mysqli_stmt_get_result($q);
    $row = mysqli_fetch_assoc($r);
    $startup_id = $row["Startup_id"] ?? null;

    // Counterpart name -> investor_id
    $q2 = mysqli_prepare($conn, "SELECT Investor_id FROM Investor_Profile WHERE Investor_name = ?");
    mysqli_stmt_bind_param($q2, "s", $counterpart);
    mysqli_stmt_execute($q2);
    $r2 = mysqli_stmt_get_result($q2);
    $row2 = mysqli_fetch_assoc($r2);
    $investor_id = $row2["Investor_id"] ?? null;
}

if (!$startup_id || !$investor_id) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Counterpart not found or profile missing"]);
    exit();
}

$date = date("Y-m-d");

$sql = "INSERT INTO Funding (Investor_id, Startup_id, Amount, Date, Status) VALUES (?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Insert prepare failed"]);
    exit();
}

$amount_f = (float)$amount;
mysqli_stmt_bind_param($stmt, "iidss", $investor_id, $startup_id, $amount_f, $date, $stage);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_errno($stmt)) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Insert failed"]);
    exit();
}

echo json_encode(["success" => true, "funding_id" => (int)mysqli_insert_id($conn)]);
?>
