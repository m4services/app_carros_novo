<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

$page_title = 'Dashboard';

try {
    require_once 'includes/header.php';
} catch (Exception $e) {
    error_log('Erro ao carregar sistema: ' . $e->getMessage());
    die('Erro ao carregar sistema. Verifique as configurações.');
}

if (isset($auth)) {
    $auth->requireLogin();
} else {
    header('Location: login.php');
    exit;
}

$error = '';
$message = '';

try {
    $vehicle = class_exists('Vehicle') ? new Vehicle() : null;
    $maintenance = class_exists('Maintenance') ? new Maintenance() : null;
    $trip = class_exists('Trip') ? new Trip() : null;
    $fleet_manager = class_exists('FleetManager') ? new FleetManager() : null;
    $notification_manager = class_exists('NotificationManager') ? new NotificationManager() : null;

    // Obter estatísticas da frota
    $fleet_stats = $fleet_manager ? $fleet_manager->getFleetStatistics() : [
        'total_vehicles' => 0,
        'available_vehicles' => 0,
        'active_trips' => 0,
        'monthly_km' => 0,
        'overdue_maintenances' => 0
    ];
    
    $vehicles = $vehicle ? $vehicle->getAll() : [];
    $available_vehicles = $vehicle ? $vehicle->getAvailable() : [];
    $overdue_maintenances = $maintenance ? $maintenance->getOverdue() : [];
    $upcoming_maintenances = $maintenance ? $maintenance->getUpcoming() : [];
    $recent_trips = $trip ? $trip->getTrips($auth->isAdmin() ? null : $_SESSION['user_id'], ['status' => 'finalizado']) : [];
    $recent_trips = array_slice($recent_trips, 0, 5);
    
    // Verificar notificações (apenas para admins)
    if ($auth->isAdmin() && $notification_manager) {
        $notification_manager->checkOverdueMaintenances();
        $notification_manager->checkExpiringLicenses();
    }
    
    // Obter veículos que precisam de manutenção
    $vehicles_needing_maintenance = $fleet_manager ? $fleet_manager->getVehiclesNeedingMaintenance() : [];
} catch (Exception $e) {
    error_log('Erro ao carregar dados do dashboard: ' . $e->getMessage());
    
    $vehicles = [];
    $available_vehicles = [];
    $overdue_maintenances = [];
    $upcoming_maintenances = [];
    $recent_trips = [];
    $fleet_stats = [
        'total_vehicles' => 0,
        'available_vehicles' => 0,
        'active_trips' => 0,
        'monthly_km' => 0,
        'overdue_maintenances' => 0
    ];
    $vehicles_needing_maintenance = [];
    
    $error = 'Erro ao carregar dados. Verifique a conexão com o banco de dados.';
}

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
        } else {
            try {
                $trip_data = [
                    'usuario_id' => $usuario_id,
                    'veiculo_id' => $veiculo_id,
                    'destino' => $destino,
                    'km_saida' => $km_saida
                ];
                
                if ($trip && $trip->startTrip($trip_data)) {
                    $message = 'Deslocamento iniciado com sucesso!';
                    echo '<script>
                        setTimeout(function() {
                            window.location.href = "finalizar-deslocamento.php";
                        }, 1500);
                    </script>';
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
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
        <div class="card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-2">
                            Total de Veículos
                        </div>
                        <div class="h3 mb-0 fw-bold text-dark"><?= $fleet_stats['total_vehicles'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-truck text-primary" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-success text-uppercase mb-2">
                            Veículos Disponíveis
                        </div>
                        <div class="h3 mb-0 fw-bold text-dark"><?= $fleet_stats['available_vehicles'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-warning text-uppercase mb-2">
                            Deslocamentos Ativos
                        </div>
                        <div class="h3 mb-0 fw-bold text-dark"><?= $fleet_stats['active_trips'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-play-circle text-warning" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-info text-uppercase mb-2">
                            KM Rodados (Mês)
                        </div>
                        <div class="h3 mb-0 fw-bold text-dark"><?= number_format($fleet_stats['monthly_km']) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-speedometer text-info" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alertas de manutenção preventiva -->
<?php if (!empty($vehicles_needing_maintenance)): ?>
<div class="alert alert-info">
    <h5><i class="bi bi-info-circle me-2"></i>Veículos Precisando de Manutenção Preventiva</h5>
    <ul class="mb-0">
        <?php foreach ($vehicles_needing_maintenance as $veiculo): ?>
            <li>
                <strong><?= escape($veiculo['nome']) ?></strong>
                <?php if ($veiculo['days_since_oil_change'] > 180): ?>
                    - Troca de óleo há <?= $veiculo['days_since_oil_change'] ?> dias
                <?php endif; ?>
                <?php if ($veiculo['km_since_oil_change'] > 10000): ?>
                    - Troca de óleo há <?= number_format($veiculo['km_since_oil_change']) ?> km
                <?php endif; ?>
                <?php if ($veiculo['days_since_alignment'] > 365): ?>
                    - Alinhamento há <?= $veiculo['days_since_alignment'] ?> dias
                <?php endif; ?>
                <?php if ($veiculo['km_since_alignment'] > 20000): ?>
                    - Alinhamento há <?= number_format($veiculo['km_since_alignment']) ?> km
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- Alertas de manutenção -->
<?php if (!empty($overdue_maintenances)): ?>
<div class="alert alert-danger">
    <h5><i class="bi bi-exclamation-triangle me-2"></i>Manutenções Vencidas</h5>
    <ul class="mb-0">
        <?php foreach ($overdue_maintenances as $manutencao): ?>
            <li><?= escape($manutencao['veiculo_nome']) ?> - <?= escape($manutencao['tipo']) ?> (<?= formatDate($manutencao['data_manutencao']) ?>)</li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if (!empty($upcoming_maintenances)): ?>
<div class="alert alert-warning">
    <h5><i class="bi bi-calendar-event me-2"></i>Próximas Manutenções (7 dias)</h5>
    <ul class="mb-0">
        <?php foreach ($upcoming_maintenances as $manutencao): ?>
            <li><?= escape($manutencao['veiculo_nome']) ?> - <?= escape($manutencao['tipo']) ?> (<?= formatDate($manutencao['data_manutencao']) ?>)</li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- Veículos -->
<div class="card mb-4">
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
                    $is_available = $fleet_manager ? $fleet_manager->isVehicleAvailable($veiculo['id']) : false;
                    $needs_maintenance = false;
                    foreach ($vehicles_needing_maintenance as $vm) {
                        if ($vm['id'] == $veiculo['id']) {
                            $needs_maintenance = true;
                            break;
                        }
                    }
                    ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                        <div class="card <?= $is_available ? ($needs_maintenance ? 'border-warning' : 'border-success') : 'border-danger' ?>" 
                             <?= $is_available ? 'style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#startTripModal" data-vehicle-id="' . $veiculo['id'] . '" data-vehicle-name="' . escape($veiculo['nome']) . '" data-vehicle-km="' . $veiculo['hodometro_atual'] . '"' : '' ?>>
                            <div class="position-relative">
                                <?php if ($veiculo['foto']): ?>
                                    <img src="<?= UPLOADS_URL ?>/veiculos/<?= escape($veiculo['foto']) ?>" 
                                         class="card-img-top" alt="<?= escape($veiculo['nome']) ?>" 
                                         style="height: 200px; object-fit: cover;<?= !$is_available ? ' opacity: 0.5; filter: grayscale(50%);' : '' ?>">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center" 
                                         style="height: 200px;<?= !$is_available ? ' opacity: 0.5;' : '' ?>">
                                        <i class="bi bi-truck text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="position-absolute top-0 end-0 m-3">
                                    <?php if ($is_available): ?>
                                        <span class="badge <?= $needs_maintenance ? 'bg-warning text-dark' : 'bg-success' ?>">
                                            <?= $needs_maintenance ? 'Manutenção' : 'Disponível' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Em uso</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <h5 class="card-title"><?= escape($veiculo['nome']) ?></h5>
                                <p class="card-text">
                                    <i class="bi bi-hash me-2"></i><?= escape($veiculo['placa']) ?><br>
                                    <i class="bi bi-speedometer me-2"></i><?= number_format($veiculo['hodometro_atual']) ?> km
                                    <?php if ($needs_maintenance): ?>
                                        <br><small class="text-warning">
                                            <i class="bi bi-exclamation-triangle me-1"></i>Precisa de manutenção
                                        </small>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Deslocamentos recentes -->
<?php if (!empty($recent_trips)): ?>
<div class="card">
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
                        <th>Data/Hora</th>
                        <th>Motorista</th>
                        <th>Veículo</th>
                        <th>Destino</th>
                        <th>KM Rodados</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_trips as $deslocamento): ?>
                        <tr>
                            <td><?= formatDateTime($deslocamento['data_inicio']) ?></td>
                            <td><?= escape($deslocamento['motorista_nome']) ?></td>
                            <td><?= escape($deslocamento['veiculo_nome']) ?></td>
                            <td><?= escape($deslocamento['destino']) ?></td>
                            <td>
                                <?= $deslocamento['km_retorno'] ? number_format($deslocamento['km_retorno'] - $deslocamento['km_saida']) . ' km' : '-' ?>
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
<div class="modal fade" id="startTripModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Iniciar Deslocamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="start_trip">
                <input type="hidden" name="veiculo_id" id="modal_veiculo_id">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Veículo Selecionado</label>
                        <input type="text" class="form-control" id="modal_veiculo_nome" readonly>
                    </div>
                    
                    <?php if ($auth->isAdmin()): ?>
                        <div class="mb-3">
                            <label for="usuario_id" class="form-label">Motorista *</label>
                            <select class="form-select" name="usuario_id" required>
                                <option value="">Selecione o motorista</option>
                                <?php 
                                if (class_exists('User')) {
                                    $user_class = new User();
                                    $drivers = $user_class->getDrivers();
                                    foreach ($drivers as $driver): 
                                ?>
                                    <option value="<?= $driver['id'] ?>"><?= escape($driver['nome']) ?></option>
                                <?php 
                                    endforeach;
                                }
                                ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="destino" class="form-label">Destino *</label>
                        <input type="text" class="form-control" name="destino" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="km_saida" class="form-label">KM de Saída *</label>
                        <input type="number" class="form-control" name="km_saida" id="km_saida" required min="0">
                        <div class="form-text">
                            <span id="km_info">Hodômetro atual: <span id="current_km">0</span> km</span>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Iniciar Deslocamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Configurar modal
    const startTripModal = document.getElementById('startTripModal');
    if (startTripModal) {
        startTripModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (button) {
                const vehicleId = button.getAttribute('data-vehicle-id');
                const vehicleName = button.getAttribute('data-vehicle-name');
                const vehicleKm = button.getAttribute('data-vehicle-km');
                
                const modalVeiculoId = document.getElementById('modal_veiculo_id');
                const modalVeiculoNome = document.getElementById('modal_veiculo_nome');
                const kmSaidaInput = document.getElementById('km_saida');
                const currentKmSpan = document.getElementById('current_km');
                
                if (modalVeiculoId) modalVeiculoId.value = vehicleId || '';
                if (modalVeiculoNome) modalVeiculoNome.value = vehicleName || '';
                if (kmSaidaInput) {
                    kmSaidaInput.min = vehicleKm || 0;
                    kmSaidaInput.value = vehicleKm || 0;
                }
                if (currentKmSpan) currentKmSpan.textContent = new Intl.NumberFormat('pt-BR').format(vehicleKm || 0);
            }
        });
    }
</script>

<?php require_once 'includes/footer.php'; ?>