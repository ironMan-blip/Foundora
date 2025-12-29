<?php
/**
 * Notifications endpoint (NO DATABASE).
 *
 * Faculty requirement: do not store notifications in DB.
 * This implementation stores notifications in the PHP session only.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth_check.php';

// Basic auth guard (auth_check.php should set session + redirect, but keep it safe)
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'error' => 'Not authenticated']);
  exit;
}

$userType = $_SESSION['user_type'] ?? 'User';

// Seed some sample notifications per session (only once)
if (!isset($_SESSION['notifications']) || !is_array($_SESSION['notifications'])) {
  $now = time();
  $_SESSION['notifications'] = [
    [
      'id'    => 'n_' . bin2hex(random_bytes(4)),
      'type'  => 'match',
      'title' => 'New match',
      'body'  => ($userType === 'Startup')
        ? 'A new investor matches your profile.'
        : 'A startup matches your preferences.',
      'at'    => date('c', $now - 3600 * 2),
      'read'  => false,
    ],
    [
      'id'    => 'n_' . bin2hex(random_bytes(4)),
      'type'  => 'message',
      'title' => 'New message',
      'body'  => 'You have a new message in your inbox.',
      'at'    => date('c', $now - 3600 * 6),
      'read'  => false,
    ],
    [
      'id'    => 'n_' . bin2hex(random_bytes(4)),
      'type'  => 'meeting',
      'title' => 'Meeting reminder',
      'body'  => 'Don\'t forget your upcoming meeting.',
      'at'    => date('c', $now - 3600 * 24),
      'read'  => true,
    ],
  ];
}

// Sort newest first
$notifications = $_SESSION['notifications'];
usort($notifications, function ($a, $b) {
  return strtotime($b['at'] ?? 'now') <=> strtotime($a['at'] ?? 'now');
});

echo json_encode([
  'success' => true,
  'notifications' => $notifications,
]);
