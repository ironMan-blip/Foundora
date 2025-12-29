<?php
    session_start();
    require_once "database.php";

    header("Content-Type: application/json; charset=utf-8");

    if (!isset($_SESSION["user_id"])) {
        http_response_code(401);
        echo json_encode(["logged_in" => false]);
        exit();
    }

    $userId = (int)$_SESSION["user_id"];

    $sql = "SELECT Name, Email, User_type FROM User WHERE User_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "db_prepare_failed"]);
        exit();
    }

    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (!$res || mysqli_num_rows($res) !== 1) {
        http_response_code(404);
        echo json_encode(["error" => "user_not_found"]);
        exit();
    }

    $row = mysqli_fetch_assoc($res);
    $name = $row["Name"] ?? "User";
    $email = $row["Email"] ?? "";
    $role = strtolower($row["User_type"] ?? "startup");

    echo json_encode([
        "logged_in" => true,
        "user" => [
            "name" => $name,
            "email" => $email,
            "role" => $role
        ]
    ]);
