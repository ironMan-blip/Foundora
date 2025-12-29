<?php
    require_once "auth_check.php";
    require_once "database.php";

    header("Content-Type: application/json; charset=utf-8");

    function respond($code, $data) {
        http_response_code($code);
        echo json_encode($data);
        exit();
    }

    function post_str($key, $default = "") {
        if (isset($_POST[$key])) {
            return trim($_POST[$key]);
        }
        return $default;
    }

    $userId   = (int)($_SESSION["user_id"] ?? 0);
    $userType = $_SESSION["user_type"] ?? "";

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        respond(405, ["ok" => false, "error" => "method_not_allowed"]);
    }

    $display_name = post_str("display_name", "");
    
    if ($display_name === "") {
        respond(400, ["ok" => false, "error" => "display_name_required"]);
    }


    $sql = "UPDATE User SET Name = ? WHERE User_id = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        respond(500, ["ok" => false, "error" => "db_prepare_user"]);
    }

    mysqli_stmt_bind_param($stmt, "si", $display_name, $userId);

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        respond(500, ["ok" => false, "error" => "db_execute_user", "details" => $err]);
    }
    mysqli_stmt_close($stmt);


    if ($userType === "Startup") {

        $startup_name = post_str("startup_name", "");
        $founder_name = post_str("founder_name", "");
        $industry     = post_str("industry", "");
        $stage        = post_str("stage", "");
        $description  = post_str("description", "");
        $funding_raw  = post_str("funding_needed", "");

        if ($startup_name === "" || $founder_name === "") {
            respond(400, ["ok" => false, "error" => "startup_required"]);
        }

        $industry    = ($industry === "") ? null : $industry;
        $stage       = ($stage === "") ? null : $stage;
        $description = ($description === "") ? null : $description;

        $funding_needed = null;
        if ($funding_raw !== "") {
            if (!is_numeric($funding_raw)) {
                respond(400, ["ok" => false, "error" => "funding_not_numeric"]);
            }
            $funding_needed = $funding_raw;
        }

        $sql = "UPDATE Startup_Profile
                SET Startup_name = ?, Founder_name = ?, Industry = ?, Stage = ?, Description = ?, Funding_needed = ?
                WHERE User_id = ?";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            respond(500, ["ok" => false, "error" => "db_prepare_startup"]);
        }

        mysqli_stmt_bind_param(
            $stmt,
            "ssssssi",
            $startup_name,
            $founder_name,
            $industry,
            $stage,
            $description,
            $funding_needed,
            $userId
        );

        if (!mysqli_stmt_execute($stmt)) {
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            respond(500, ["ok" => false, "error" => "db_execute_startup", "details" => $err]);
        }
        mysqli_stmt_close($stmt);

        respond(200, ["ok" => true, "role" => "startup", "display_name" => $display_name]);
    }


    if ($userType === "Investor") {

        $investor_name = post_str("investor_name", "");
        if ($investor_name === "") {
            respond(400, ["ok" => false, "error" => "investor_name_required"]);
        }

        $investor_type      = post_str("investor_type", "");
        $investor_range     = post_str("investor_range", "");
        $sector_of_interest = post_str("sector_of_interest", "");

        $investor_type      = ($investor_type === "") ? null : $investor_type;
        $investor_range     = ($investor_range === "") ? null : $investor_range;
        $sector_of_interest = ($sector_of_interest === "") ? null : $sector_of_interest;

        $sql = "UPDATE Investor_Profile
                SET Investor_name = ?, Investor_type = ?, Investor_range = ?, Sector_of_interest = ?
                WHERE User_id = ?";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            respond(500, ["ok" => false, "error" => "db_prepare_investor"]);
        }

        mysqli_stmt_bind_param(
            $stmt,
            "ssssi",
            $investor_name,
            $investor_type,
            $investor_range,
            $sector_of_interest,
            $userId
        );

        if (!mysqli_stmt_execute($stmt)) {
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            respond(500, ["ok" => false, "error" => "db_execute_investor", "details" => $err]); // CHANGED: include stmt error
        }
        mysqli_stmt_close($stmt);

        respond(200, ["ok" => true, "role" => "investor", "display_name" => $display_name]);
    }

    respond(400, ["ok" => false, "error" => "unknown_user_type"]);
