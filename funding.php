<?php
session_start();
header("Content-Type: application/json");
require_once "database.php";

if (!isset($_SESSION["user_id"], $_SESSION["user_type"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit();
}

$user_id   = (int)$_SESSION["user_id"];
$user_type = $_SESSION["user_type"];

if ($user_type === "Startup") {

    $sql = "
        SELECT 
            f.Funding_id,
            f.Amount,
            f.Date,
            f.Status,
            i.Investor_name AS counterpart
        FROM Funding f
        JOIN Startup_Profile sp ON f.Startup_id = sp.Startup_id
        JOIN Investor_Profile i ON f.Investor_id = i.Investor_id
        WHERE sp.User_id = ?
        ORDER BY f.Date DESC
    ";

} else {

    $sql = "
        SELECT 
            f.Funding_id,
            f.Amount,
            f.Date,
            f.Status,
            sp.Startup_name AS counterpart
        FROM Funding f
        JOIN Investor_Profile ip ON f.Investor_id = ip.Investor_id
        JOIN Startup_Profile sp ON f.Startup_id = sp.Startup_id
        WHERE ip.User_id = ?
        ORDER BY f.Date DESC
    ";
}

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Query prepare failed"]);
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$res = mysqli_stmt_get_result($stmt);
$deals = [];

while ($row = mysqli_fetch_assoc($res)) {
    $deals[] = [
        "id"          => (int)$row["Funding_id"],
        "counterpart" => $row["counterpart"],
        "amount"      => (float)$row["Amount"],
        "stage"       => $row["Status"] ?? "Intro",
        "date"        => $row["Date"],
        // Frontend sorts by `updated`; use date as a stable value.
        "updated"     => $row["Date"]
    ];
}

echo json_encode(["success" => true, "deals" => $deals]);
?>
