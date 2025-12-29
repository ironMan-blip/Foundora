<?php
    ob_start();
    session_start();
    require_once "database.php";

    function go($url) {
        header("Location: " . $url);
        exit();
    }

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        go("index.html");
    }

    $action = strtolower(trim($_POST["action"] ?? ""));
    $role   = strtolower(trim($_POST["role"] ?? ""));

    $name     = trim($_POST["name"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($action !== "login" && $action !== "signup") {
        go("index.html?error=bad_action");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        go("index.html?error=bad_email");
    }
    if ($password === "") {
        go("index.html?error=empty_password");
    }

    //LOGIN

    if ($action === "login") {

        $sql = "SELECT User_id, Password, User_type FROM User WHERE Email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if(!$stmt) go("index.html?error=db_prepare");

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);

        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);

            if (password_verify($password, $user["Password"])) {
                $_SESSION["user_id"]   = (int)$user["User_id"];
                $_SESSION["user_type"] = $user["User_type"];

                go("dashboard.php");
            }
        }

        go("index.html?error=invalid_login");

    }


    //SIGNUP

    $password_confirm = $_POST["password_confirm"] ?? "";

    if ($name === ""){
        go("index.html?error=empty_name");
    }

    if (strlen($password) < 8) {
        go("index.html?error=weak_password");
    }

    if ($password !== $password_confirm) {
        go("index.html?error=password_mismatch");
    }

    if ($role !== "startup" && $role !== "investor") {
        go("index.html?error=bad_role");
    }

    $sql = "SELECT User_id FROM User WHERE Email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) go("index.html?error=db_prepare");

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        go("index.html?error=email_exists");
    }
    
    $user_type = ($role === "startup") ? "Startup" : "Investor";
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO User (Name, Email, Password, User_type) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) go("index.html?error=db_prepare");

    mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hashed, $user_type);

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        go("index.html?error=user_insert_failed");
    }

    mysqli_stmt_close($stmt);
    $user_id = mysqli_insert_id($conn);

    if ($role === "startup") {

        $startup_name = trim($_POST["startup_name"] ?? "");
        $founder_name  = trim($_POST["founder_name"] ?? "");

        if ($startup_name === "" || $founder_name === "") {

            go("index.html?error=startup_required");

        }

        $industry = trim($_POST["industry"] ?? "");
        $stage = trim($_POST["stage"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $funding_needed = $_POST["funding_needed"] ?? null;

        $industry = ($industry === "") ? null : $industry;
        $stage = ($stage === "") ? null : $stage;
        $description = ($description === "") ? null : $description;

        if ($funding_needed === "" || $funding_needed === null) {
            $funding_needed = null;
        }

        $sql = "INSERT INTO Startup_Profile
            (User_id, Startup_name, Founder_name, Industry, Description, Stage, Funding_needed)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) go("index.html?error=db_prepare");
        
        mysqli_stmt_bind_param($stmt, "isssssd", $user_id, $startup_name, $founder_name, $industry, $description, $stage, $funding_needed);

        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            go("index.html?error=startup_profile_failed");
        }

        mysqli_stmt_close($stmt);

    }

    else {

        $investor_name = trim($_POST["investor_name"] ?? "");
        if ($investor_name === "") {
            go("index.html?error=investor_required");
        }

        $investor_type = trim($_POST["investor_type"] ?? "");
        $investor_range = trim($_POST["investor_range"] ?? "");
        $sector_of_interest = trim($_POST["sector_of_interest"] ?? "");

        $investor_type = ($investor_type === "") ? null : $investor_type;
        $investor_range = ($investor_range === "") ? null : $investor_range;
        $sector_of_interest = ($sector_of_interest === "") ? null : $sector_of_interest;

        $sql = "INSERT INTO Investor_Profile
            (User_id, Investor_name, Investor_type, Investor_range, Sector_of_interest)
            VALUES (?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) go("index.html?error=db_prepare");

        mysqli_stmt_bind_param($stmt, "issss",
            $user_id, $investor_name, $investor_type, $investor_range, $sector_of_interest
        );

        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            go("index.html?error=investor_profile_failed");
        }

        mysqli_stmt_close($stmt);

    }

    $_SESSION["user_id"]   = (int)$user_id;
    $_SESSION["user_type"] = $user_type;

    go("dashboard.php");

?>