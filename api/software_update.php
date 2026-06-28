<?php
// api/software_update.php
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/updater_helper.php';

header('Content-Type: application/json');
requireLogin();

if ($_SESSION['user_role'] !== 'admin') {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

// CSRF check on all mutation actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$updater = new OBJSIS_Updater($pdo);

try {
  switch ($action) {

    case 'check_update':
      $result = $updater->checkUpdate();
      echo json_encode($result);
      break;

    case 'update_progress':
      echo json_encode($updater->stepProgress());
      break;

    case 'update_step': {
      $step = (int)($_POST['step'] ?? 0);
      $updateData = [
        'url'    => $_POST['url']    ?? '',
        'sql_url'=> $_POST['sql_url'] ?? '',
      ];
      $result = $updater->runStep($step, $updateData);
      echo json_encode($result);
      break;
    }

    default:
      echo json_encode(['success' => false, 'message' => 'Invalid action']);
  }
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
