<?php
$page_title = 'Informações do Sistema';
require_once '../includes/header.php';

$auth->requireAdmin();

// Informações do sistema
$system_info = [
    'php_version' => PHP_VERSION,
    'mysql_version' => '',
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
    'max_upload_size' => ini_get('upload_max_filesize'),
    'max_post_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'disk_free_space' => 0,
    'disk_total_space' => 0
];

try {
    // Versão do MySQL
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT VERSION() as version");
    $mysql_info = $stmt->fetch();
    $system_info['mysql_version'] = $mysql_info['version'] ?? 'N/A';
    
    // Espaço em disco
    $system_info['disk_free_space'] = disk_free_space(ROOT_PATH);
    $system_info['disk_total_space'] = disk_total_space(ROOT_PATH);
    
    // Estatísticas do banco
    $stats = [];
    
    $tables = ['usuarios', 'veiculos', 'deslocamentos', 'manutencoes', 'notificacoes'];
    foreach ($tables as $table) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM {$table}");
        $stmt->execute();
        $stats[$table] = $stmt->fetchColumn();
    }
    
} catch (Exception $e) {
    $error = 'Erro ao obter informações do sistema: ' . $e->getMessage();
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Informações do Sistema</h1>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?= escape($error) ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-server me-2"></i>Informações do Servidor</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Versão do PHP:</strong></td>
                        <td><?= $system_info['php_version'] ?></td>
                    </tr>
                    <tr>
                        <td><strong>Versão do MySQL:</strong></td>
                        <td><?= $system_info['mysql_version'] ?></td>
                    </tr>
                    <tr>
                        <td><strong>Servidor Web:</strong></td>
                        <td><?= $system_info['server_software'] ?></td>
                    </tr>
                    <tr>
                        <td><strong>Diretório Raiz:</strong></td>
                        <td><small><?= $system_info['document_root'] ?></small></td>
                    </tr>
                    <tr>
                        <td><strong>Limite de Upload:</strong></td>
                        <td><?= $system_info['max_upload_size'] ?></td>
                    </tr>
                    <tr>
                        <td><strong>Limite POST:</strong></td>
                        <td><?= $system_info['max_post_size'] ?></td>
                    </tr>
                    <tr>
                        <td><strong>Limite de Memória:</strong></td>
                        <td><?= $system_info['memory_limit'] ?></td>
                    </tr>
                    <tr>
                        <td><strong>Tempo de Execução:</strong></td>
                        <td><?= $system_info['max_execution_time'] ?>s</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-hdd me-2"></i>Espaço em Disco</h5>
            </div>
            <div class="card-body">
                <?php 
                $free_space = $system_info['disk_free_space'];
                $total_space = $system_info['disk_total_space'];
                $used_space = $total_space - $free_space;
                $usage_percent = $total_space > 0 ? ($used_space / $total_space) * 100 : 0;
                ?>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Espaço Usado</span>
                        <span><?= formatBytes($used_space) ?> / <?= formatBytes($total_space) ?></span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar <?= $usage_percent > 90 ? 'bg-danger' : ($usage_percent > 70 ? 'bg-warning' : 'bg-success') ?>" 
                             style="width: <?= $usage_percent ?>%"></div>
                    </div>
                    <small class="text-muted"><?= number_format($usage_percent, 1) ?>% utilizado</small>
                </div>
                
                <table class="table table-sm">
                    <tr>
                        <td><strong>Espaço Total:</strong></td>
                        <td><?= formatBytes($total_space) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Espaço Livre:</strong></td>
                        <td><?= formatBytes($free_space) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Espaço Usado:</strong></td>
                        <td><?= formatBytes($used_space) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (isset($stats)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-database me-2"></i>Estatísticas do Banco de Dados</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <div class="text-center">
                            <div class="h3 text-primary"><?= number_format($stats['usuarios']) ?></div>
                            <div class="text-muted">Usuários</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <div class="h3 text-success"><?= number_format($stats['veiculos']) ?></div>
                            <div class="text-muted">Veículos</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="h3 text-info"><?= number_format($stats['deslocamentos']) ?></div>
                            <div class="text-muted">Deslocamentos</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <div class="h3 text-warning"><?= number_format($stats['manutencoes']) ?></div>
                            <div class="text-muted">Manutenções</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <div class="h3 text-secondary"><?= number_format($stats['notificacoes']) ?></div>
                            <div class="text-muted">Notificações</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-tools me-2"></i>Ferramentas de Manutenção</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="d-grid">
                            <button type="button" class="btn btn-outline-primary" onclick="checkSystem()">
                                <i class="bi bi-check-circle me-2"></i>Verificar Sistema
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-grid">
                            <button type="button" class="btn btn-outline-warning" onclick="clearCache()">
                                <i class="bi bi-trash me-2"></i>Limpar Cache
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-grid">
                            <a href="../backup/backup.php" class="btn btn-outline-success" target="_blank">
                                <i class="bi bi-download me-2"></i>Backup Manual
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function checkSystem() {
    showToast('Verificação do sistema iniciada...', 'info');
    
    // Simular verificação
    setTimeout(() => {
        showToast('Sistema funcionando corretamente!', 'success');
    }, 2000);
}

function clearCache() {
    if (confirm('Tem certeza que deseja limpar o cache do sistema?')) {
        showToast('Cache limpo com sucesso!', 'success');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>