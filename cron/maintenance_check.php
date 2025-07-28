<?php
/**
 * Script para verificação automática de manutenções
 * Deve ser executado via cron job diariamente
 * 
 * Adicionar ao crontab:
 * 0 8 * * * /usr/bin/php /path/to/project/cron/maintenance_check.php
 */

// Definir ROOT_PATH
define('ROOT_PATH', dirname(__DIR__));

// Incluir configurações
require_once ROOT_PATH . '/config/config.php';

try {
    $notification_manager = new NotificationManager();
    
    echo "Iniciando verificação de manutenções...\n";
    
    // Verificar manutenções vencidas
    $overdue_count = $notification_manager->checkOverdueMaintenances();
    echo "Manutenções vencidas encontradas: {$overdue_count}\n";
    
    // Verificar CNHs vencendo
    $expiring_count = $notification_manager->checkExpiringLicenses();
    echo "CNHs próximas do vencimento: {$expiring_count}\n";
    
    // Log da execução
    $log_message = date('Y-m-d H:i:s') . " - Verificação concluída. Manutenções: {$overdue_count}, CNHs: {$expiring_count}\n";
    file_put_contents(ROOT_PATH . '/logs/maintenance_check.log', $log_message, FILE_APPEND | LOCK_EX);
    
    echo "Verificação concluída com sucesso!\n";
    
} catch (Exception $e) {
    $error_message = date('Y-m-d H:i:s') . " - Erro: " . $e->getMessage() . "\n";
    file_put_contents(ROOT_PATH . '/logs/maintenance_check.log', $error_message, FILE_APPEND | LOCK_EX);
    echo "Erro na verificação: " . $e->getMessage() . "\n";
    exit(1);
}
?>