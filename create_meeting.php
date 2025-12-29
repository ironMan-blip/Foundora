<?php
session_start();
require_once "database.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["user_id"], $_SESSION["user_type"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Not logged in"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Invalid JSON body"]);
    exit();
}

// Frontend (meetings.html) sends: { with, mode, date, time, agenda }
$withWho = trim((string)($data["with"] ?? ""));
$date    = trim((string)($data["date"] ?? ""));
$time    = trim((string)($data["time"] ?? ""));

if ($withWho === "" || $date === "" || $time === "") {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Missing required fields"]);
    exit();
}

// Normalize scheduled datetime
$dt = date_create_from_format('Y-m-d H:i', "$date $time");
if (!$dt) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Invalid date/time format"]);
    exit();
}
$scheduled_time = $dt->format('Y-m-d H:i:s');

$user_id   = (int)$_SESSION["user_id"];
$user_type = $_SESSION["user_type"]; // 'Startup' or 'Investor'

// Resolve current user's profile id + the other party's profile id by name
if ($user_type === "Startup") {
    // Get Startup_id for current user
    $q = mysqli_prepare($conn, "SELECT Startup_id FROM Startup_Profile WHERE User_id = ? LIMIT 1");
    mysqli_stmt_bind_param($q, "i", $user_id);
    mysqli_stmt_execute($q);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
    if (!$row) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Startup profile not found for this user"]);
        exit();
    }
    $startup_id = (int)$row["Startup_id"];

    // Find Investor_id by name (exact match first, then LIKE)
    $q = mysqli_prepare($conn, "SELECT Investor_id FROM Investor_Profile WHERE Investor_name = ? LIMIT 1");
    mysqli_stmt_bind_param($q, "s", $withWho);
    mysqli_stmt_execute($q);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($q));

    if (!$row) {
        $like = "%" . $withWho . "%";
        $q = mysqli_prepare($conn, "SELECT Investor_id FROM Investor_Profile WHERE Investor_name LIKE ? LIMIT 1");
        mysqli_stmt_bind_param($q, "s", $like);
        mysqli_stmt_execute($q);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
    }

    if (!$row) {
        http_response_code(404);
        echo json_encode(["success" => false, "error" => "Investor not found: $withWho"]);
        exit();
    }
    $investor_id = (int)$row["Investor_id"];

} elseif ($user_type === "Investor") {
    // Get Investor_id for current user
    $q = mysqli_prepare($conn, "SELECT Investor_id FROM Investor_Profile WHERE User_id = ? LIMIT 1");
    mysqli_stmt_bind_param($q, "i", $user_id);
    mysqli_stmt_execute($q);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
    if (!$row) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Investor profile not found for this user"]);
        exit();
    }
    $investor_id = (int)$row["Investor_id"];

    // Find Startup_id by name (exact match first, then LIKE)
    $q = mysqli_prepare($conn, "SELECT Startup_id FROM Startup_Profile WHERE Startup_name = ? LIMIT 1");
    mysqli_stmt_bind_param($q, "s", $withWho);
    mysqli_stmt_execute($q);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($q));

    if (!$row) {
        $like = "%" . $withWho . "%";
        $q = mysqli_prepare($conn, "SELECT Startup_id FROM Startup_Profile WHERE Startup_name LIKE ? LIMIT 1");
        mysqli_stmt_bind_param($q, "s", $like);
        mysqli_stmt_execute($q);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
    }

    if (!$row) {
        http_response_code(404);
        echo json_encode(["success" => false, "error" => "Startup not found: $withWho"]);
        exit();
    }
    $startup_id = (int)$row["Startup_id"];

} else {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Invalid user type"]);
    exit();
}

// Create meeting
$sql = "INSERT INTO Meeting (Startup_id, Investor_id, Scheduled_time, Status) VALUES (?, ?, ?, 'Scheduled')";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "SQL prepare failed"]);
    exit();
}

mysqli_stmt_bind_param($stmt, "iis", $startup_id, $investor_id, $scheduled_time);

if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Insert failed"]);
    exit();
}

echo json_encode([
    "success" => true,
    "meeting_id" => mysqli_insert_id($conn)
]);
