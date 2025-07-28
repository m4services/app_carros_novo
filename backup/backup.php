<?php
/**
 * Script de backup automático do banco de dados
 * Deve ser executado via cron job
 * 
 * Adicionar ao crontab:
 * 0 2 * * * /usr/bin/php /path/to/project/backup/backup.php
 */

// Definir ROOT_PATH
define('ROOT_PATH', dirname(__DIR__));

// Incluir configurações
require_once ROOT_PATH . '/config/config.php';

try {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $database = $_ENV['DB_NAME'] ?? 'sistema_veiculos';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASS'] ?? '';
    
    // Criar diretório de backup se não existir
    $backup_dir = ROOT_PATH . '/backup/files';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    // Nome do arquivo de backup
    $backup_file = $backup_dir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    // Comando mysqldump
    $command = "mysqldump --host={$host} --user={$username}";
    if (!empty($password)) {
        $command .= " --password={$password}";
    }
    $command .= " --single-transaction --routines --triggers {$database} > {$backup_file}";
    
    // Executar backup
    exec($command, $output, $return_code);
    
    if ($return_code === 0 && file_exists($backup_file) && filesize($backup_file) > 0) {
        // Compactar arquivo
        $zip_file = $backup_file . '.zip';
        $zip = new ZipArchive();
        
        if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($backup_file, basename($backup_file));
            $zip->close();
            
            // Remover arquivo SQL original
            unlink($backup_file);
            
            echo "Backup criado com sucesso: " . basename($zip_file) . "\n";
            
            // Remover backups antigos (manter apenas os últimos 7 dias)
            $files = glob($backup_dir . '/backup_*.sql.zip');
            if (count($files) > 7) {
                // Ordenar por data de modificação
                usort($files, function($a, $b) {
                    return filemtime($a) - filemtime($b);
                });
                
                // Remover os mais antigos
                $to_remove = array_slice($files, 0, count($files) - 7);
                foreach ($to_remove as $file) {
                    unlink($file);
                    echo "Backup antigo removido: " . basename($file) . "\n";
                }
            }
            
            // Log do backup
            $log_message = date('Y-m-d H:i:s') . " - Backup criado: " . basename($zip_file) . " (" . formatBytes(filesize($zip_file)) . ")\n";
            file_put_contents(ROOT_PATH . '/logs/backup.log', $log_message, FILE_APPEND | LOCK_EX);
            
        } else {
            throw new Exception('Erro ao criar arquivo ZIP');
        }
    } else {
        throw new Exception('Erro ao executar mysqldump');
    }
    
} catch (Exception $e) {
    $error_message = date('Y-m-d H:i:s') . " - Erro no backup: " . $e->getMessage() . "\n";
    file_put_contents(ROOT_PATH . '/logs/backup.log', $error_message, FILE_APPEND | LOCK_EX);
    echo "Erro no backup: " . $e->getMessage() . "\n";
    exit(1);
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}
?>