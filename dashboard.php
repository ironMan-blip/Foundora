<?php
    require_once "auth_check.php";
    require_once "database.php";

    function go($url) {
    header("Location: " . $url);
    exit();
    }

    $userId = (int)($_SESSION["user_id"] ?? 0);

    $name = "User";
    $email = "";
    $role = "startup";

    $sql = "SELECT Name, Email, User_type FROM User WHERE User_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) go("index.html?error=db_prepare");
    
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if ($res && mysqli_num_rows($res) === 1) {
        $row = mysqli_fetch_assoc($res);
        $name = $row["Name"] ?: "User";
        $email = $row["Email"] ?: "";
        $role = strtolower($row["User_type"] ?? "startup");
    }
    mysqli_stmt_close($stmt);

    $html = @file_get_contents("dashboard.html");

    if ($html === false) {
        go("index.html?error=dashboard_missing");
    }

    $user = [
        "name" => $name,
        "email" => $email,
        "role" => $role,
        "loggedInAt" => date("c"),
        "mode" => "session"
    ];
    $userJson = json_encode($user, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $inject = "<script>\n"
            . "  try {\n"
            . "    const user = {$userJson};\n"
            . "    localStorage.setItem('foundora_user', JSON.stringify(user));\n"
            . "  } catch(e) {}\n"
            . "</script>\n";

    if (strpos($html, '<script src="./shared.js"></script>') !== false) {
        $html = str_replace('<script src="./shared.js"></script>', $inject . '<script src="./shared.js"></script>', $html);
    } else {
        $html = str_replace("</body>", $inject . "</body>", $html);
    }

    echo $html;

?>