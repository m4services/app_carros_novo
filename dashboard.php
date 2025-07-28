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
                    // Redirecionar imediatamente
                    header('Location: finalizar-deslocamento.php');
                    exit;
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}
?>

<div class="dashboard-container">
    <!-- Header da Dashboard -->
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1 class="welcome-title">Olá, <?= escape($_SESSION['user_name'] ?? 'Usuário') ?>!</h1>
            <p class="welcome-subtitle">Bem-vindo ao sistema de controle de veículos</p>
        </div>
        <div class="quick-actions">
            <button class="btn-quick-action" data-bs-toggle="modal" data-bs-target="#quickStartModal">
                <i class="bi bi-plus-circle"></i>
                <span>Novo Deslocamento</span>
            </button>
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

    <!-- Alertas de manutenção preventiva -->
    <?php if (!empty($vehicles_needing_maintenance)): ?>
    <div class="maintenance-alert">
        <div class="alert-icon">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <div class="alert-content">
            <h5>Veículos Precisando de Manutenção Preventiva</h5>
            <ul class="maintenance-list">
                <?php foreach ($vehicles_needing_maintenance as $veiculo): ?>
                    <li>
                        <strong><?= escape($veiculo['nome']) ?></strong>
                        <?php if ($veiculo['days_since_oil_change'] > 180): ?>
                            - Troca de óleo há <?= $veiculo['days_since_oil_change'] ?> dias
                        <?php endif; ?>
                        <?php if ($veiculo['km_since_oil_change'] > 10000): ?>
                            - Troca de óleo há <?= number_format($veiculo['km_since_oil_change']) ?> km
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- Alertas de manutenção -->
    <?php if (!empty($overdue_maintenances)): ?>
    <div class="maintenance-alert danger">
        <div class="alert-icon">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <div class="alert-content">
            <h5>Manutenções Vencidas</h5>
            <ul class="maintenance-list">
                <?php foreach ($overdue_maintenances as $manutencao): ?>
                    <li><?= escape($manutencao['veiculo_nome']) ?> - <?= escape($manutencao['tipo']) ?> (<?= formatDate($manutencao['data_manutencao']) ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- Veículos Disponíveis -->
    <div class="vehicles-section">
        <div class="section-header">
            <h2>Veículos Disponíveis</h2>
            <div class="section-actions">
                <button class="btn-refresh" onclick="refreshVehicles()">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <?php if ($auth->isAdmin()): ?>
                    <a href="veiculo-form.php" class="btn-add">
                        <i class="bi bi-plus"></i>
                        Novo Veículo
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="vehicles-grid" id="vehiclesGrid">
            <?php if (empty($vehicles)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="bi bi-truck"></i>
                    </div>
                    <h3>Nenhum veículo cadastrado</h3>
                    <p>Cadastre o primeiro veículo para começar a usar o sistema.</p>
                    <?php if ($auth->isAdmin()): ?>
                        <a href="veiculo-form.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Cadastrar Primeiro Veículo
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
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
                    <div class="vehicle-card <?= $is_available ? ($needs_maintenance ? 'warning' : 'available') : 'unavailable' ?>" 
                         <?= $is_available ? 'onclick="selectVehicle(' . $veiculo['id'] . ', \'' . escape($veiculo['nome']) . '\', ' . $veiculo['hodometro_atual'] . ')"' : '' ?>>
                        <div class="vehicle-image">
                            <?php if ($veiculo['foto']): ?>
                                <img src="<?= UPLOADS_URL ?>/veiculos/<?= escape($veiculo['foto']) ?>" 
                                     alt="<?= escape($veiculo['nome']) ?>">
                            <?php else: ?>
                                <div class="vehicle-placeholder">
                                    <i class="bi bi-truck"></i>
                                </div>
                            <?php endif; ?>
                            <div class="vehicle-status">
                                <?php if ($is_available): ?>
                                    <span class="status-badge <?= $needs_maintenance ? 'warning' : 'available' ?>">
                                        <?= $needs_maintenance ? 'Manutenção' : 'Disponível' ?>
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge unavailable">Em uso</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="vehicle-info">
                            <h3><?= escape($veiculo['nome']) ?></h3>
                            <div class="vehicle-details">
                                <span class="plate"><?= escape($veiculo['placa']) ?></span>
                                <span class="km"><?= number_format($veiculo['hodometro_atual']) ?> km</span>
                            </div>
                            <?php if ($needs_maintenance): ?>
                                <div class="maintenance-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    Precisa de manutenção
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Deslocamentos recentes -->
    <?php if (!empty($recent_trips)): ?>
    <div class="recent-trips-section">
        <div class="section-header">
            <h2>Deslocamentos Recentes</h2>
            <a href="relatorios.php" class="btn-view-all">Ver Todos</a>
        </div>
        <div class="trips-list">
            <?php foreach ($recent_trips as $deslocamento): ?>
                <div class="trip-item">
                    <div class="trip-icon">
                        <i class="bi bi-geo-alt"></i>
                    </div>
                    <div class="trip-info">
                        <div class="trip-destination"><?= escape($deslocamento['destino']) ?></div>
                        <div class="trip-details">
                            <?= escape($deslocamento['veiculo_nome']) ?> • 
                            <?= formatDateTime($deslocamento['data_inicio']) ?>
                        </div>
                    </div>
                    <div class="trip-km">
                        <?= $deslocamento['km_retorno'] ? number_format($deslocamento['km_retorno'] - $deslocamento['km_saida']) . ' km' : '-' ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal para iniciar deslocamento -->
<div class="modal fade" id="quickStartModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Iniciar Novo Deslocamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="startTripForm">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="start_trip">
                <input type="hidden" name="veiculo_id" id="selected_vehicle_id">
                
                <div class="modal-body">
                    <div class="vehicle-selection" id="vehicleSelection">
                        <h6>Selecione um Veículo</h6>
                        <div class="vehicle-options">
                            <?php foreach ($available_vehicles as $veiculo): ?>
                                <div class="vehicle-option" data-vehicle-id="<?= $veiculo['id'] ?>" 
                                     data-vehicle-name="<?= escape($veiculo['nome']) ?>" 
                                     data-vehicle-km="<?= $veiculo['hodometro_atual'] ?>">
                                    <div class="vehicle-option-image">
                                        <?php if ($veiculo['foto']): ?>
                                            <img src="<?= UPLOADS_URL ?>/veiculos/<?= escape($veiculo['foto']) ?>" 
                                                 alt="<?= escape($veiculo['nome']) ?>">
                                        <?php else: ?>
                                            <i class="bi bi-truck"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="vehicle-option-info">
                                        <div class="name"><?= escape($veiculo['nome']) ?></div>
                                        <div class="details"><?= escape($veiculo['placa']) ?> • <?= number_format($veiculo['hodometro_atual']) ?> km</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="trip-form" id="tripForm" style="display: none;">
                        <div class="selected-vehicle-info" id="selectedVehicleInfo"></div>
                        
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
                            <input type="text" class="form-control" name="destino" required placeholder="Para onde você está indo?">
                        </div>
                        
                        <div class="mb-3">
                            <label for="km_saida" class="form-label">KM de Saída *</label>
                            <input type="number" class="form-control" name="km_saida" id="km_saida" required min="0">
                            <div class="form-text">
                                <span id="km_info">Hodômetro atual: <span id="current_km">0</span> km</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-primary" id="backToSelection" style="display: none;">
                        <i class="bi bi-arrow-left me-2"></i>Voltar
                    </button>
                    <button type="submit" class="btn btn-primary" id="startTripBtn" style="display: none;">
                        <i class="bi bi-play-circle me-2"></i>Iniciar Deslocamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let selectedVehicleData = null;

// Função para selecionar veículo
function selectVehicle(id, name, km) {
    selectedVehicleData = { id, name, km };
    document.getElementById('selected_vehicle_id').value = id;
    document.getElementById('current_km').textContent = new Intl.NumberFormat('pt-BR').format(km);
    document.getElementById('km_saida').value = km;
    document.getElementById('km_saida').min = km;
    
    // Mostrar informações do veículo selecionado
    document.getElementById('selectedVehicleInfo').innerHTML = `
        <div class="selected-vehicle">
            <i class="bi bi-truck me-2"></i>
            <strong>${name}</strong> - Hodômetro: ${new Intl.NumberFormat('pt-BR').format(km)} km
        </div>
    `;
    
    // Mostrar formulário
    document.getElementById('vehicleSelection').style.display = 'none';
    document.getElementById('tripForm').style.display = 'block';
    document.getElementById('backToSelection').style.display = 'inline-block';
    document.getElementById('startTripBtn').style.display = 'inline-block';
    
    // Abrir modal se não estiver aberto
    const modal = new bootstrap.Modal(document.getElementById('quickStartModal'));
    modal.show();
}

// Voltar para seleção de veículo
document.getElementById('backToSelection').addEventListener('click', function() {
    document.getElementById('vehicleSelection').style.display = 'block';
    document.getElementById('tripForm').style.display = 'none';
    this.style.display = 'none';
    document.getElementById('startTripBtn').style.display = 'none';
});

// Seleção de veículo no modal
document.querySelectorAll('.vehicle-option').forEach(option => {
    option.addEventListener('click', function() {
        const id = this.dataset.vehicleId;
        const name = this.dataset.vehicleName;
        const km = this.dataset.vehicleKm;
        selectVehicle(id, name, km);
    });
});

// Refresh de veículos
function refreshVehicles() {
    const refreshBtn = document.querySelector('.btn-refresh');
    refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i>';
    
    fetch(window.location.href)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newGrid = doc.getElementById('vehiclesGrid');
            if (newGrid) {
                document.getElementById('vehiclesGrid').innerHTML = newGrid.innerHTML;
            }
        })
        .catch(error => {
            console.error('Erro ao atualizar veículos:', error);
            showToast('Erro ao atualizar veículos', 'danger');
        })
        .finally(() => {
            refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
        });
}

// Auto-refresh a cada 30 segundos
setInterval(refreshVehicles, 30000);

// Reset modal ao fechar
document.getElementById('quickStartModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('vehicleSelection').style.display = 'block';
    document.getElementById('tripForm').style.display = 'none';
    document.getElementById('backToSelection').style.display = 'none';
    document.getElementById('startTripBtn').style.display = 'none';
    document.getElementById('startTripForm').reset();
});
</script>

<?php require_once 'includes/footer.php'; ?>