<?php
header('Content-Type: application/json');
require_once '../config/config.php';

$auth = new Auth();
$auth->requireLogin();

$notification_manager = new NotificationManager();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        $notifications = $notification_manager->getUnreadNotifications($_SESSION['user_id']);
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'count' => count($notifications)
        ]);
        break;
        
    case 'mark_read':
        $id = (int)($_POST['id'] ?? 0);
        $success = $notification_manager->markAsRead($id, $_SESSION['user_id']);
        echo json_encode(['success' => $success]);
        break;
        
    case 'mark_all_read':
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                UPDATE notificacoes 
                SET lida = 1, data_leitura = NOW() 
                WHERE usuario_id = ? AND lida = 0
            ");
            $success = $stmt->execute([$_SESSION['user_id']]);
            echo json_encode(['success' => $success]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Ação inválida']);
}
?>