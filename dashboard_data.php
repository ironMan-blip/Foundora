<?php
// dashboard_data.php (DB-backed stats for dashboard.html)
// -----------------------------------------------------
// This file returns JSON used by dashboard.html to show:
//  - Matches count (startups or investors)
//  - Unread messages count (sum of unread per conversation)
//  - Upcoming meetings count
//
// NO database writes happen here.

require_once "auth_check.php";
require_once "database.php";

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

function respond($code, $payload) {
    http_response_code($code);
    echo json_encode($payload);
    exit();
}

$userId = (int)($_SESSION["user_id"] ?? 0);
$userTypeRaw = (string)($_SESSION["user_type"] ?? "");
$userType = strtolower(trim($userTypeRaw)); // "investor" or "startup"

if ($userId <= 0 || ($userType !== "investor" && $userType !== "startup")) {
    respond(401, ["ok" => false, "error" => "not_authenticated"]);
}

$data = [
    "startups" => [],
    "investors" => [],
    "conversations" => [],
    "meetings" => []
];

// ------------------------------
// 1) Matches (Top matches preview)
// ------------------------------
if ($userType === "investor") {
    // Simple list of startups (for Top Matches preview)
    $sql = "SELECT sp.Startup_id AS id,
                   sp.Startup_name AS name,
                   sp.Industry AS industry,
                   sp.Stage AS stage,
                   sp.Funding_needed AS needs
            FROM Startup_Profile sp
            ORDER BY sp.Startup_id DESC
            LIMIT 30";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $data["startups"][] = [
                "id" => (int)$row["id"],
                "name" => $row["name"],
                "industry" => $row["industry"] ?? "",
                "stage" => $row["stage"] ?? "",
                "location" => "", // not in DB schema
                "needs" => $row["needs"] ?? ""
            ];
        }
    }
} else {
    // startup -> show investors
    $sql = "SELECT ip.Investor_id AS id,
                   ip.Investor_name AS name,
                   ip.Investor_type AS type,
                   ip.Sector_of_interest AS focus,
                   ip.Investor_range AS ticket
            FROM Investor_Profile ip
            ORDER BY ip.Investor_id DESC
            LIMIT 30";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $data["investors"][] = [
                "id" => (int)$row["id"],
                "name" => $row["name"],
                "type" => $row["type"] ?? "",
                "focus" => $row["focus"] ?? "",
                "location" => "", // not in DB schema
                "ticket" => $row["ticket"] ?? ""
            ];
        }
    }
}

// ------------------------------
// 2) Conversations + unread counts
// ------------------------------
// We build a list of "other users" that this user has messaged with,
// plus the latest message and how many unread messages are waiting.
$convSql = "
SELECT
  other_id,
  MAX(last_time) AS last_time
FROM (
  SELECT Receiver_id AS other_id, Timestamp AS last_time
  FROM Message
  WHERE Sender_id = {$userId}
  UNION ALL
  SELECT Sender_id AS other_id, Timestamp AS last_time
  FROM Message
  WHERE Receiver_id = {$userId}
) t
GROUP BY other_id
ORDER BY last_time DESC
LIMIT 50
";
$convRes = mysqli_query($conn, $convSql);

if ($convRes) {
    while ($c = mysqli_fetch_assoc($convRes)) {
        $otherId = (int)$c["other_id"];

        // Other user's display name
        $name = "";
        $nameRes = mysqli_query($conn, "SELECT Name FROM User WHERE User_id = {$otherId} LIMIT 1");
        if ($nameRes && ($nr = mysqli_fetch_assoc($nameRes))) {
            $name = $nr["Name"] ?? "";
        }

        // Last message preview
        $lastSql = "SELECT Content, Timestamp, Sender_id, Receiver_id
                    FROM Message
                    WHERE (Sender_id = {$userId} AND Receiver_id = {$otherId})
                       OR (Sender_id = {$otherId} AND Receiver_id = {$userId})
                    ORDER BY Timestamp DESC
                    LIMIT 1";
        $lastRes = mysqli_query($conn, $lastSql);
        $last = $lastRes ? mysqli_fetch_assoc($lastRes) : null;

        // Unread count: messages sent by other -> me that are unseen
        $unreadSql = "SELECT COUNT(*) AS c
                      FROM Message
                      WHERE Sender_id = {$otherId}
                        AND Receiver_id = {$userId}
                        AND Seen_status = 0";
        $unreadRes = mysqli_query($conn, $unreadSql);
        $unreadRow = $unreadRes ? mysqli_fetch_assoc($unreadRes) : ["c" => 0];
        $unread = (int)($unreadRow["c"] ?? 0);

        $data["conversations"][] = [
            "with_id" => $otherId,
            // dashboard.html expects `with` for display in some places
            "with" => ($name !== "" ? $name : ("User #" . $otherId)),
            "name" => ($name !== "" ? $name : ("User #" . $otherId)),
            "last" => $last["Content"] ?? "",
            "at" => $last["Timestamp"] ?? null,
            "unread" => $unread
        ];
    }
}

// ------------------------------
// 3) Upcoming Meetings
// ------------------------------
// Meeting table uses Startup_id + Investor_id (NOT user_id). So we must map user -> profile id.
$profileId = 0;

if ($userType === "investor") {
    $pRes = mysqli_query($conn, "SELECT Investor_id FROM Investor_Profile WHERE User_id = {$userId} LIMIT 1");
    if ($pRes && ($pr = mysqli_fetch_assoc($pRes))) {
        $profileId = (int)($pr["Investor_id"] ?? 0);
    }

    if ($profileId > 0) {
        $mSql = "SELECT Meeting_id, Scheduled_time, Status
                 FROM Meeting
                 WHERE Investor_id = {$profileId}
                 ORDER BY Scheduled_time DESC
                 LIMIT 50";
        $mRes = mysqli_query($conn, $mSql);
        if ($mRes) {
            while ($m = mysqli_fetch_assoc($mRes)) {
                $data["meetings"][] = [
                    "id" => (int)$m["Meeting_id"],
                    "when" => $m["Scheduled_time"],
                    "status" => $m["Status"] ?? ""
                ];
            }
        }
    }
} else {
    $pRes = mysqli_query($conn, "SELECT Startup_id FROM Startup_Profile WHERE User_id = {$userId} LIMIT 1");
    if ($pRes && ($pr = mysqli_fetch_assoc($pRes))) {
        $profileId = (int)($pr["Startup_id"] ?? 0);
    }

    if ($profileId > 0) {
        $mSql = "SELECT Meeting_id, Scheduled_time, Status
                 FROM Meeting
                 WHERE Startup_id = {$profileId}
                 ORDER BY Scheduled_time DESC
                 LIMIT 50";
        $mRes = mysqli_query($conn, $mSql);
        if ($mRes) {
            while ($m = mysqli_fetch_assoc($mRes)) {
                $data["meetings"][] = [
                    "id" => (int)$m["Meeting_id"],
                    "when" => $m["Scheduled_time"],
                    "status" => $m["Status"] ?? ""
                ];
            }
        }
    }
}

respond(200, ["ok" => true, "data" => $data]);
