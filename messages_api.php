<?php
// messages_api.php
// Backend for messages.html (DB version)
// ----------------------------------------------------
// GET:
//   ?action=list
//   ?action=thread&with=OTHER_USER_ID
// POST (x-www-form-urlencoded):
//   action=send&to=OTHER_USER_ID&content=TEXT
//
// Table used (Foundora.sql):
//   Message(Message_id, Sender_id, Receiver_id, Content, Timestamp, Seen_status)

require_once "auth_check.php";
require_once "database.php";

header("Content-Type: application/json; charset=utf-8");

function respond($code, $data) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

$userId = (int)($_SESSION["user_id"] ?? 0);
if ($userId <= 0) {
    respond(401, ["ok" => false, "error" => "not_logged_in"]);
}

$action = strtolower(trim($_REQUEST["action"] ?? ""));

/**
 * Get a nice display name for a user id.
 * Falls back to User.Name if profile names are missing.
 */
function getDisplayName(mysqli $conn, int $uid): string {
    $sql = "SELECT u.Name, u.User_type,
                   sp.Startup_name,
                   ip.Investor_name
            FROM User u
            LEFT JOIN Startup_Profile sp ON sp.User_id = u.User_id
            LEFT JOIN Investor_Profile ip ON ip.User_id = u.User_id
            WHERE u.User_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return "User";
    mysqli_stmt_bind_param($stmt, "i", $uid);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    if (!$row) return "User";
    $type = $row["User_type"] ?? "";
    if ($type === "Startup" && !empty($row["Startup_name"])) return $row["Startup_name"];
    if ($type === "Investor" && !empty($row["Investor_name"])) return $row["Investor_name"];
    return $row["Name"] ?? "User";
}

/**
 * LIST: Return conversation list for the current user.
 * We build conversations in PHP by scanning latest messages.
 */
if ($action === "list") {
    $sql = "SELECT Message_id, Sender_id, Receiver_id, Content, Timestamp, Seen_status
            FROM Message
            WHERE Sender_id = ? OR Receiver_id = ?
            ORDER BY Timestamp DESC, Message_id DESC";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) respond(500, ["ok" => false, "error" => "db_prepare_list"]);
    mysqli_stmt_bind_param($stmt, "ii", $userId, $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $conversations = []; // otherId => data
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        $sender = (int)$row["Sender_id"];
        $receiver = (int)$row["Receiver_id"];
        $otherId = ($sender === $userId) ? $receiver : $sender;

        if (!isset($conversations[$otherId])) {
            // first time we see this otherId (because ORDER BY DESC)
            $conversations[$otherId] = [
                "other_id" => $otherId,
                "with" => getDisplayName($conn, $otherId),
                "name" => getDisplayName($conn, $otherId),
                "last" => $row["Content"] ?? "",
                "at" => $row["Timestamp"] ?? "",
                "unread" => 0
            ];
        }

        // Count unread messages that are sent TO me and not seen
        if ($receiver === $userId && (int)($row["Seen_status"] ?? 0) === 0) {
            $conversations[$otherId]["unread"] += 1;
        }
    }
    mysqli_stmt_close($stmt);

    // Convert map to list
    $list = array_values($conversations);

    respond(200, ["ok" => true, "conversations" => $list]);
}

/**
 * THREAD: Return messages between current user and other user.
 * Also marks messages as seen (only those sent to me).
 */
if ($action === "thread") {
    $otherId = (int)($_GET["with"] ?? 0);
    if ($otherId <= 0) respond(400, ["ok" => false, "error" => "missing_with"]);

    // Mark unseen messages from other -> me as seen
    $markSql = "UPDATE Message
                SET Seen_status = 1
                WHERE Sender_id = ? AND Receiver_id = ? AND Seen_status = 0";
    $markStmt = mysqli_prepare($conn, $markSql);
    if ($markStmt) {
        mysqli_stmt_bind_param($markStmt, "ii", $otherId, $userId);
        mysqli_stmt_execute($markStmt);
        mysqli_stmt_close($markStmt);
    }

    $sql = "SELECT Message_id, Sender_id, Receiver_id, Content, Timestamp, Seen_status
            FROM Message
            WHERE (Sender_id = ? AND Receiver_id = ?)
               OR (Sender_id = ? AND Receiver_id = ?)
            ORDER BY Timestamp ASC, Message_id ASC";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) respond(500, ["ok" => false, "error" => "db_prepare_thread"]);
    mysqli_stmt_bind_param($stmt, "iiii", $userId, $otherId, $otherId, $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $msgs = [];
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        $by = ((int)$row["Sender_id"] === $userId) ? "me" : "them";
        $msgs[] = [
            "id" => (int)$row["Message_id"],
            "by" => $by,
            "text" => $row["Content"] ?? "",
            "at" => $row["Timestamp"] ?? "",
            "seen" => (int)($row["Seen_status"] ?? 0)
        ];
    }
    mysqli_stmt_close($stmt);

    respond(200, [
        "ok" => true,
        "with" => $otherId,
        "with_name" => getDisplayName($conn, $otherId),
        "messages" => $msgs
    ]);
}

/**
 * SEND: Insert a new message.
 */
if ($action === "send") {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        respond(405, ["ok" => false, "error" => "method_not_allowed"]);
    }

    $to = (int)($_POST["to"] ?? 0);
    $content = trim((string)($_POST["content"] ?? ""));

    if ($to <= 0) respond(400, ["ok" => false, "error" => "missing_to"]);
    if ($content === "") respond(400, ["ok" => false, "error" => "empty_content"]);

    $sql = "INSERT INTO Message (Sender_id, Receiver_id, Content, Timestamp, Seen_status)
            VALUES (?, ?, ?, NOW(), 0)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) respond(500, ["ok" => false, "error" => "db_prepare_send"]);

    mysqli_stmt_bind_param($stmt, "iis", $userId, $to, $content);
    $ok = mysqli_stmt_execute($stmt);
    if (!$ok) {
        $err = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        respond(500, ["ok" => false, "error" => "db_execute_send", "details" => $err]);
    }

    $newId = (int)mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    respond(200, ["ok" => true, "message_id" => $newId]);
}

respond(400, ["ok" => false, "error" => "bad_action"]);
?>
