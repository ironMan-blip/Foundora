<?php
// search_data.php
// Returns search list items from DB (used by search.html).
//
// Response format:
//   { ok: true, target: "startups"|"investors", items: [...] }
//
// NOTE: This file is DB-backed (search needs DB to show real profiles).
// Faculty restriction was only for NOTIFICATIONS (no DB), so this is OK.

require_once "auth_check.php";
require_once "database.php";

header("Content-Type: application/json; charset=utf-8");

function respond($code, $data) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$userType = $_SESSION["user_type"] ?? "";

if ($userId <= 0) {
    respond(401, ["ok" => false, "error" => "not_logged_in"]);
}

// Normalize type just in case
$userType = ucfirst(strtolower(trim($userType))); // "Startup" / "Investor"

/* ======================
   INVESTOR -> SEE STARTUPS
   ====================== */
if ($userType === "Investor") {
    $sql = "SELECT u.User_id,
                   u.Name AS display_name,
                   sp.Startup_name,
                   sp.Industry,
                   sp.Stage,
                   sp.Description,
                   sp.Funding_needed
            FROM User u
            INNER JOIN Startup_Profile sp ON sp.User_id = u.User_id
            WHERE u.User_type = 'Startup'
            ORDER BY sp.Startup_name ASC";

    $res = mysqli_query($conn, $sql);
    if (!$res) {
        respond(500, ["ok" => false, "error" => "db_query_failed", "details" => mysqli_error($conn)]);
    }

    $items = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $id = (int)$r["User_id"];
        $items[] = [
            // search.html expects `id`
            "id" => $id,
            "name" => ($r["Startup_name"] ?: ($r["display_name"] ?: "Startup")),
            "industry" => $r["Industry"] ?? "",
            "stage" => $r["Stage"] ?? "",
            "tagline" => $r["Description"] ?? "",
            "needs" => (float)($r["Funding_needed"] ?? 0),
            "location" => "" // not in DB schema, keep empty
        ];
    }

    respond(200, ["ok" => true, "target" => "startups", "items" => $items]);
}

/* ======================
   STARTUP -> SEE INVESTORS
   ====================== */
if ($userType === "Startup") {
    $sql = "SELECT u.User_id,
                   u.Name AS display_name,
                   ip.Investor_name,
                   ip.Investor_type,
                   ip.Investor_range,
                   ip.Sector_of_interest
            FROM User u
            INNER JOIN Investor_Profile ip ON ip.User_id = u.User_id
            WHERE u.User_type = 'Investor'
            ORDER BY ip.Investor_name ASC";

    $res = mysqli_query($conn, $sql);
    if (!$res) {
        respond(500, ["ok" => false, "error" => "db_query_failed", "details" => mysqli_error($conn)]);
    }

    $items = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $id = (int)$r["User_id"];
        $items[] = [
            "id" => $id,
            "name" => ($r["Investor_name"] ?: ($r["display_name"] ?: "Investor")),
            "type" => $r["Investor_type"] ?? "",
            "focus" => $r["Sector_of_interest"] ?? "",
            "ticket" => $r["Investor_range"] ?? "",
            "location" => "" // not in DB schema
        ];
    }

    respond(200, ["ok" => true, "target" => "investors", "items" => $items]);
}

respond(400, ["ok" => false, "error" => "unknown_user_type"]);
?>
