<?php
// Definir ROOT_PATH se não estiver definido
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

$page_title = 'Dashboard';

try {
    require_once 'includes/header.php';
} catch (Exception $e) {
    error_log('Erro ao carregar header: ' . $e->getMessage());
    
    // Em desenvolvimento, mostrar erro detalhado
    $is_production = ($_ENV['APP_ENV'] ?? 'development') === 'production';
    if (!$is_production) {
        die('<div style="padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px; font-family: Arial, sans-serif;">
            <h3>Erro no Dashboard</h3>
            <p><strong>Erro:</strong> ' . $e->getMessage() . '</p>
            <p><strong>Arquivo:</strong> ' . $e->getFile() . '</p>
            <p><strong>Linha:</strong> ' . $e->getLine() . '</p>
        </div>');
    } else {
        die('Erro interno do servidor. Tente novamente em alguns minutos.');
    }
}

$auth->requireLogin();

try {
    $vehicle = new Vehicle();
    $maintenance = new Maintenance();
    $trip = new Trip();

    $vehicles = $vehicle->getAll();
    $available_vehicles = $vehicle->getAvailable();
    $overdue_maintenances = $maintenance->getOverdue();
    $upcoming_maintenances = $maintenance->getUpcoming();
    $recent_trips = $trip->getTrips($auth->isAdmin() ? null : $_SESSION['user_id'], ['status' => 'finalizado']);
    $recent_trips = array_slice($recent_trips, 0, 5);
} catch (Exception $e) {
    error_log('Erro ao carregar dados do dashboard: ' . $e->getMessage());
    
    // Definir valores padrão para evitar erros
    $vehicles = [];
    $available_vehicles = [];
    $overdue_maintenances = [];
    $upcoming_maintenances = [];
    $recent_trips = [];
    
    $error = 'Erro ao carregar dados do dashboard. Verifique a conexão com o banco de dados.';
}

$message = '';
$error = $error ?? '';

// Processar início de deslocamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'start_trip') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido.';
    } else {
        $veiculo_id = (int)($_POST['veiculo_id'] ?? 0);
        $usuario_id = $auth->isAdmin() ? (int)($_POST['usuario_id'] ?? 0) : $_SESSION['user_id'];
        $destino = trim($_POST['destino'] ?? '');
        $km_saida = (int)($_POST['km_saida'] ?? 0);
        
        if (!$veiculo_id || !$usuario_id || !$destino || !$km_saida) {
            $error = 'Por favor, preencha todos os campos obrigatórios.';
        } else if (!$vehicle->isAvailable($veiculo_id)) {
            $error = 'Veículo não está disponível.';
        } else if ($auth->hasActiveTrip($usuario_id)) {
            $error = 'Usuário já possui um deslocamento ativo.';
        } else {
            $trip_data = [
                'usuario_id' => $usuario_id,
                'veiculo_id' => $veiculo_id,
                'destino' => $destino,
                'km_saida' => $km_saida
            ];
            
            if ($trip->startTrip($trip_data)) {
                redirect('/finalizar-deslocamento.php');
            } else {
                $error = 'Erro ao iniciar deslocamento.';
            }
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <span class="badge bg-success">Online</span>
        </div>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?= escape($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <?= escape($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Cards de estatísticas -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card animate-slide-in-left">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-2">
                            Total de Veículos
                        </div>
                        <div class="h3 mb-0 fw-bold text-dark"><?= count($vehicles) ?></div>
                    </div>
                    <div class="col-auto">
                        <div class="stats-icon">
                            <i class="bi bi-truck"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card animate-slide-in-left" style="animation-delay: 0.1s;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-success text-uppercase mb-2">
                            Veículos Disponíveis
                        </div>
                        <div class="h3 mb-0 fw-bold text-dark"><?= count($available_vehicles) ?></div>
                    </div>
                    <div class="col-auto">
                        <div class="stats-icon bg-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card animate-slide-in-left" style="animation-delay: 0.2s;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-warning text-uppercase mb-2">
                            Manutenções Vencidas
                        </div>
                        <div class="h3 mb-0 fw-bold text-dark"><?= count($overdue_maintenances) ?></div>
                    </div>
                    <div class="col-auto">
                        <div class="stats-icon bg-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card animate-slide-in-left" style="animation-delay: 0.3s;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-info text-uppercase mb-2">
                            Próximas Manutenções
                        </div>
                        <div class="h3 mb-0 fw-bold text-dark"><?= count($upcoming_maintenances) ?></div>
                    </div>
                    <div class="col-auto">
                        <div class="stats-icon bg-info">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alertas de manutenção -->
<?php if (!empty($overdue_maintenances)): ?>
<div class="alert alert-danger animate-fade-in-up">
    <h5><i class="bi bi-exclamation-triangle me-2"></i>Manutenções Vencidas</h5>
    <ul class="mb-0">
        <?php foreach ($overdue_maintenances as $manutencao): ?>
            <li><?= escape($manutencao['veiculo_nome']) ?> - <?= escape($manutencao['tipo']) ?> (<?= formatDate($manutencao['data_manutencao']) ?>)</li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if (!empty($upcoming_maintenances)): ?>
<div class="alert alert-warning animate-fade-in-up">
    <h5><i class="bi bi-calendar-event me-2"></i>Próximas Manutenções (7 dias)</h5>
    <ul class="mb-0">
        <?php foreach ($upcoming_maintenances as $manutencao): ?>
            <li><?= escape($manutencao['veiculo_nome']) ?> - <?= escape($manutencao['tipo']) ?> (<?= formatDate($manutencao['data_manutencao']) ?>)</li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- Veículos -->
<div class="card mb-4 animate-fade-in-up">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="bi bi-truck me-2"></i>Veículos</h4>
        <?php if ($auth->isAdmin()): ?>
            <a href="veiculo-form.php" class="btn btn-light btn-sm">
                <i class="bi bi-plus-circle me-2"></i>Novo Veículo
            </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($vehicles)): ?>
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="bi bi-truck text-muted" style="font-size: 4rem;"></i>
                </div>
                <h5 class="text-muted mb-3">Nenhum veículo cadastrado</h5>
                <p class="text-muted mb-4">Cadastre o primeiro veículo para começar a usar o sistema.</p>
                <?php if ($auth->isAdmin()): ?>
                    <a href="veiculo-form.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-plus-circle me-2"></i>Cadastrar Primeiro Veículo
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($vehicles as $veiculo): ?>
                    <?php 
                    $is_available = $vehicle->isAvailable($veiculo['id']);
                    ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 mb-4 animate-fade-in-up" style="animation-delay: <?= $loop_index * 0.1 ?>s;">
                        <div class="card vehicle-card <?= $is_available ? '' : 'unavailable' ?>" 
                             <?= $is_available ? 'data-bs-toggle="modal" data-bs-target="#startTripModal" data-vehicle-id="' . $veiculo['id'] . '" data-vehicle-name="' . escape($veiculo['nome']) . '"' : '' ?>>
                            <div class="position-relative">
                                <?php if ($veiculo['foto']): ?>
                                    <img src="<?= UPLOADS_URL ?>/veiculos/<?= escape($veiculo['foto']) ?>" 
                                         class="card-img-top" alt="<?= escape($veiculo['nome']) ?>" 
                                         style="height: 220px; object-fit: cover; transition: transform 0.3s ease;"
                                         onmouseover="this.style.transform='scale(1.05)'"
                                         onmouseout="this.style.transform='scale(1)'">
                                <?php else: ?>
                                    <div class="bg-gradient d-flex align-items-center justify-content-center" 
                                         style="height: 220px; background: linear-gradient(135deg, #f8f9fa, #e9ecef);">
                                        <i class="bi bi-truck text-muted" style="font-size: 3.5rem;"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="position-absolute top-0 end-0 m-3">
                                    <span class="badge <?= $is_available ? 'bg-success' : 'bg-danger' ?> px-3 py-2">
                                        <?= $is_available ? 'Disponível' : 'Em uso' ?>
                                    </span>
                                </div>
                                
                                <?php if ($is_available): ?>
                                    <div class="position-absolute bottom-0 start-0 end-0 p-3">
                                        <div class="bg-dark bg-opacity-75 text-white text-center py-2 rounded">
                                            <small><i class="bi bi-cursor-fill me-1"></i>Clique para iniciar deslocamento</small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body p-4">
                                <h5 class="card-title mb-3 fw-bold"><?= escape($veiculo['nome']) ?></h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-1">
                                            <i class="bi bi-hash me-2 text-primary"></i>
                                            <span class="fw-semibold"><?= escape($veiculo['placa']) ?></span>
                                        </p>
                                        <p class="mb-0">
                                            <i class="bi bi-speedometer me-2 text-primary"></i>
                                            <span class="fw-semibold"><?= number_format($veiculo['hodometro_atual']) ?> km</span>
                                        </p>
                                    </div>
                                    <?php if ($is_available): ?>
                                        <i class="bi bi-play-circle-fill text-success" style="font-size: 1.5rem;"></i>
                                    <?php else: ?>
                                        <i class="bi bi-pause-circle-fill text-danger" style="font-size: 1.5rem;"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php $loop_index = ($loop_index ?? 0) + 1; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Deslocamentos recentes -->
<?php if (!empty($recent_trips)): ?>
<div class="card animate-fade-in-up">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="bi bi-clock-history me-2"></i>Deslocamentos Recentes</h4>
        <a href="relatorios.php" class="btn btn-light btn-sm">
            <i class="bi bi-eye me-2"></i>Ver Todos
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="fw-bold">Data/Hora</th>
                        <th class="fw-bold">Motorista</th>
                        <th class="fw-bold">Veículo</th>
                        <th class="fw-bold">Destino</th>
                        <th class="fw-bold">KM Rodados</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_trips as $deslocamento): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-calendar3 me-2 text-primary"></i>
                                    <span class="fw-semibold"><?= formatDateTime($deslocamento['data_inicio']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-circle me-2 text-primary"></i>
                                    <span><?= escape($deslocamento['motorista_nome']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-truck me-2 text-primary"></i>
                                    <span><?= escape($deslocamento['veiculo_nome']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-geo-alt me-2 text-primary"></i>
                                    <span><?= escape($deslocamento['destino']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-speedometer me-2 text-primary"></i>
                                    <span class="fw-bold text-success">
                                        <?= $deslocamento['km_retorno'] ? number_format($deslocamento['km_retorno'] - $deslocamento['km_saida']) . ' km' : '-' ?>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal para iniciar deslocamento -->
<div class="modal fade" id="startTripModal" tabindex="-1" aria-labelledby="startTripModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="startTripModalLabel">
                    <i class="bi bi-play-circle me-2"></i>Iniciar Deslocamento
                </h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="start_trip">
                <input type="hidden" name="veiculo_id" id="modal_veiculo_id">
                
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Veículo Selecionado</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-truck"></i></span>
                            <input type="text" class="form-control" id="modal_veiculo_nome" readonly>
                        </div>
                    </div>
                    
                    <?php if ($auth->isAdmin()): ?>
                        <div class="mb-4">
                            <label for="usuario_id" class="form-label fw-bold">
                                Motorista <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <select class="form-select" name="usuario_id" id="usuario_id" required>
                                <option value="">Selecione o motorista</option>
                                <?php 
                                $user_class = new User();
                                $drivers = $user_class->getDrivers();
                                foreach ($drivers as $driver): 
                                ?>
                                    <option value="<?= $driver['id'] ?>"><?= escape($driver['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            </div>
                            <div class="invalid-feedback">Por favor, selecione o motorista.</div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <label for="destino" class="form-label fw-bold">
                            Destino <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                            <input type="text" class="form-control" name="destino" id="destino" required 
                                   placeholder="Para onde será o deslocamento?">
                        </div>
                        <div class="invalid-feedback">Por favor, informe o destino.</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="km_saida" class="form-label fw-bold">
                            KM de Saída <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-speedometer"></i></span>
                            <input type="number" class="form-control" name="km_saida" id="km_saida" required min="0" 
                                   placeholder="Quilometragem atual do veículo">
                            <span class="input-group-text">km</span>
                        </div>
                        <div class="invalid-feedback">Por favor, informe a quilometragem de saída.</div>
                    </div>
                    
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Atenção:</strong> Após iniciar o deslocamento, você será redirecionado para a tela de finalização e não poderá acessar outras partes do sistema até finalizar o deslocamento.
                    </div>
                </div>
                
                <div class="modal-footer p-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <span class="loading spinner-border spinner-border-sm me-2"></span>
                        <i class="bi bi-play-circle me-2"></i>
                        Iniciar Deslocamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Configurar modal de deslocamento
    const startTripModal = document.getElementById('startTripModal');
    startTripModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const vehicleId = button.getAttribute('data-vehicle-id');
        const vehicleName = button.getAttribute('data-vehicle-name');
        
        document.getElementById('modal_veiculo_id').value = vehicleId;
        document.getElementById('modal_veiculo_nome').value = vehicleName;
        
        // Limpar campos
        document.getElementById('destino').value = '';
        document.getElementById('km_saida').value = '';
        
        // Focar no primeiro campo
        setTimeout(() => {
            <?php if ($auth->isAdmin()): ?>
                document.getElementById('usuario_id').focus();
            <?php else: ?>
                document.getElementById('destino').focus();
            <?php endif; ?>
        }, 300);
    });
    
    // Animações de entrada
    document.addEventListener('DOMContentLoaded', function() {
        // Adicionar delay progressivo aos cards de veículos
        const vehicleCards = document.querySelectorAll('.vehicle-card');
        vehicleCards.forEach((card, index) => {
            card.style.animationDelay = (index * 0.1) + 's';
        });
        
        // Efeito de hover nos cards de estatísticas
        const statsCards = document.querySelectorAll('.stats-card');
        statsCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>