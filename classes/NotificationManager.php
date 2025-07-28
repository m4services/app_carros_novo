<?php
class NotificationManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Cria uma notificação
     */
    public function createNotification($user_id, $title, $message, $type = 'info', $action_url = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notificacoes (usuario_id, titulo, mensagem, tipo, url_acao, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            return $stmt->execute([$user_id, $title, $message, $type, $action_url]);
        } catch (Exception $e) {
            error_log('Erro ao criar notificação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém notificações não lidas do usuário
     */
    public function getUnreadNotifications($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM notificacoes 
                WHERE usuario_id = ? AND lida = 0 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute([$user_id]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Erro ao obter notificações: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Marca notificação como lida
     */
    public function markAsRead($notification_id, $user_id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE notificacoes 
                SET lida = 1, data_leitura = NOW() 
                WHERE id = ? AND usuario_id = ?
            ");
            
            return $stmt->execute([$notification_id, $user_id]);
        } catch (Exception $e) {
            error_log('Erro ao marcar notificação como lida: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica manutenções vencidas e cria notificações
     */
    public function checkOverdueMaintenances() {
        try {
            // Buscar manutenções vencidas
            $stmt = $this->db->prepare("
                SELECT m.*, v.nome as veiculo_nome 
                FROM manutencoes m
                JOIN veiculos v ON m.veiculo_id = v.id
                WHERE m.data_manutencao < CURDATE() 
                AND m.status = 'agendada'
                AND NOT EXISTS (
                    SELECT 1 FROM notificacoes n 
                    WHERE n.tipo = 'maintenance_overdue' 
                    AND n.mensagem LIKE CONCAT('%', m.id, '%')
                    AND n.created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
                )
            ");
            $stmt->execute();
            $overdue = $stmt->fetchAll();
            
            // Buscar administradores
            $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE perfil = 'administrador'");
            $stmt->execute();
            $admins = $stmt->fetchAll();
            
            // Criar notificações para cada manutenção vencida
            foreach ($overdue as $maintenance) {
                $title = 'Manutenção Vencida';
                $message = "A manutenção '{$maintenance['tipo']}' do veículo {$maintenance['veiculo_nome']} está vencida desde " . formatDate($maintenance['data_manutencao']);
                $url = '/manutencoes.php?filter=overdue';
                
                foreach ($admins as $admin) {
                    $this->createNotification($admin['id'], $title, $message, 'warning', $url);
                }
            }
            
            return count($overdue);
        } catch (Exception $e) {
            error_log('Erro ao verificar manutenções vencidas: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Verifica CNHs próximas do vencimento
     */
    public function checkExpiringLicenses() {
        try {
            $stmt = $this->db->prepare("
                SELECT id, nome, validade_cnh,
                       DATEDIFF(validade_cnh, CURDATE()) as days_to_expire
                FROM usuarios 
                WHERE validade_cnh BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                AND NOT EXISTS (
                    SELECT 1 FROM notificacoes n 
                    WHERE n.usuario_id = usuarios.id 
                    AND n.tipo = 'license_expiring'
                    AND n.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                )
            ");
            $stmt->execute();
            $expiring = $stmt->fetchAll();
            
            foreach ($expiring as $user) {
                $title = 'CNH Próxima do Vencimento';
                $message = "Sua CNH vence em {$user['days_to_expire']} dias (" . formatDate($user['validade_cnh']) . "). Providencie a renovação.";
                
                $this->createNotification($user['id'], $title, $message, 'warning', '/perfil.php');
                
                // Notificar administradores também
                $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE perfil = 'administrador'");
                $stmt->execute();
                $admins = $stmt->fetchAll();
                
                foreach ($admins as $admin) {
                    $admin_message = "A CNH do usuário {$user['nome']} vence em {$user['days_to_expire']} dias.";
                    $this->createNotification($admin['id'], $title, $admin_message, 'warning', '/usuarios.php');
                }
            }
            
            return count($expiring);
        } catch (Exception $e) {
            error_log('Erro ao verificar CNHs vencendo: ' . $e->getMessage());
            return 0;
        }
    }
}
?>