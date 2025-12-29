<?php
    session_start();
    require_once "database.php";

    header("Content-Type: application/json; charset=utf-8");
    
    function respond($code, $data) {
        http_response_code($code);
        echo json_encode($data);
        exit();
    }

    $userId = (int)($_SESSION["user_id"] ?? 0);

    if($userId <= 0) {
        respond(401, ["ok" => false, "error" => "not logged in"]);
    }

    $sql = "SELECT Name, Email, User_type FROM User WHERE User_id = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        respond(500, ["ok" => false, "error" => "db_prepare_user"]);
    }
    
    mysqli_stmt_bind_param($stmt, "i", $userId);

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        respond(500, ["ok" => false, "error" => "db_execute_user", "details" => $err]);
    }

    $res = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if (!$res || mysqli_num_rows($res) !== 1) {
        respond(404, ["ok" => false, "error" => "user_not_found"]);
    }

    $row = mysqli_fetch_assoc($res);

    $display_name = $row["Name"] ?? "User";
    $email = $row["Email"] ?? "";
    $userType = $row["User_type"] ?? ($_SESSION["user_type"] ?? "Startup");

    $role = strtolower($userType);

    if ($role === "startup") {
        $sql = "SELECT Startup_name, Founder_name, Industry, Description, Stage, Funding_needed
            FROM Startup_Profile
            WHERE User_id = ?";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            respond(500, ["ok" => false, "error" => "db_prepare_startup"]);
        }

        mysqli_stmt_bind_param($stmt, "i", $userId);

        if (!mysqli_stmt_execute($stmt)) {
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            respond(500, ["ok" => false, "error" => "db_execute_startup", "details" => $err]);
        }

        $res = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);

        $startup = ["startup_name" => "", "founder_name" => "", "industry" => null, 
                    "description" => null, "stage" => null, "funding_needed" => null];
        
        
        if ($res && mysqli_num_rows($res) === 1) {
            $sp = mysqli_fetch_assoc($res);
            $startup = [
                "startup_name" => $sp["Startup_name"] ?? "",
                "founder_name" => $sp["Founder_name"] ?? "",
                "industry" => $sp["Industry"] ?? null,
                "description" => $sp["Description"] ?? null,
                "stage" => $sp["Stage"] ?? null,
                "funding_needed" => $sp["Funding_needed"] ?? null
            ];
        }

        respond(200, [
            "ok" => true,
            "role" => "startup",
            "display_name" => $display_name,
            "email" => $email,
            "startup" => $startup
        ]);
    }


    if ($role === "investor") {

        $sql = "SELECT Investor_name, Investor_type, Investor_range, Sector_of_interest
                FROM Investor_Profile
                WHERE User_id = ?";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            respond(500, ["ok" => false, "error" => "db_prepare_investor"]);
        }

        mysqli_stmt_bind_param($stmt, "i", $userId);

        if (!mysqli_stmt_execute($stmt)) {
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            respond(500, ["ok" => false, "error" => "db_execute_investor", "details" => $err]);
        }

        $res = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);

        $investor = ["investor_name" => "", "investor_type" => null, "investor_range" => null, "sector_of_interest" => null];

        if ($res && mysqli_num_rows($res) === 1) {
            $ip = mysqli_fetch_assoc($res);
            $investor = [
                "investor_name" => $ip["Investor_name"] ?? "",
                "investor_type" => $ip["Investor_type"] ?? null,
                "investor_range" => $ip["Investor_range"] ?? null,
                "sector_of_interest" => $ip["Sector_of_interest"] ?? null
            ];
        }

        respond(200, [
            "ok" => true,
            "role" => "investor",
            "display_name" => $display_name,
            "email" => $email,
            "investor" => $investor
        ]);
    }

    respond(400, ["ok" => false, "error" => "unknown_user_type"]);
