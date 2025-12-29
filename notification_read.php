<?php
/**
 * Mark all notifications as read (NO DATABASE).
 *
 * This updates the notifications stored in the PHP session.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth_check.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'error' => 'Not authenticated']);
  exit;
}

if (!isset($_SESSION['notifications']) || !is_array($_SESSION['notifications'])) {
  $_SESSION['notifications'] = [];
}

foreach ($_SESSION['notifications'] as &$n) {
  $n['read'] = true;
}
unset($n);

echo json_encode(['success' => true]);
