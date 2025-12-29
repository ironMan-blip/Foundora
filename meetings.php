<?php
session_start();
require_once "database.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["user_id"], $_SESSION["user_type"])) {
    http_response_code(401);
    exit();
}

$user_id   = $_SESSION["user_id"];
$user_type = $_SESSION["user_type"];

if ($user_type === "Startup") {

    $sql = "
        SELECT 
            m.Meeting_id,
            m.Scheduled_time,
            m.Status,
            i.Investor_name AS meet_with
        FROM Meeting m
        JOIN Startup_Profile sp ON m.Startup_id = sp.Startup_id
        JOIN Investor_Profile i ON m.Investor_id = i.Investor_id
        WHERE sp.User_id = ?
        ORDER BY m.Scheduled_time ASC
    ";

} else {

    $sql = "
        SELECT 
            m.Meeting_id,
            m.Scheduled_time,
            m.Status,
            s.Startup_name AS meet_with
        FROM Meeting m
        JOIN Investor_Profile ip ON m.Investor_id = ip.Investor_id
        JOIN Startup_Profile s ON m.Startup_id = s.Startup_id
        WHERE ip.User_id = ?
        ORDER BY m.Scheduled_time ASC
    ";
}

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$out = [];

while ($row = mysqli_fetch_assoc($result)) {
    $out[] = [
        "id"     => $row["Meeting_id"],
        "with"   => $row["meet_with"],
        "when"   => $row["Scheduled_time"],
        "status" => $row["Status"]
    ];
}

echo json_encode([
    "success"  => true,
    "meetings" => $out
]);
