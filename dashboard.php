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

    $vehicles = $vehicle ? $vehicle->getAll() : [];
    $available_vehicles = $vehicle ? $vehicle->getAvailable() : [];
    $overdue_maintenances = $maintenance ? $maintenance->getOverdue() : [];
    $upcoming_maintenances = $maintenance ? $maintenance->getUpcoming() : [];
    $recent_trips = $trip ? $trip->getTrips($auth->isAdmin() ? null : $_SESSION['user_id'], ['status' => 'finalizado']) : [];
    $recent_trips = array_slice($recent_trips, 0, 5);
    
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
                    redirect('/finalizar-deslocamento.php');
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2">Olá, <?= escape($_SESSION['user_name'] ?? 'Usuário') ?>!</h1>
        <p class="text-muted">Bem-vindo ao sistema de controle de veículos</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tripModal">
            <i class="bi bi-plus-circle me-2"></i>Novo Deslocamento
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

<!-- Alertas de manutenção preventiva -->
<?php if (!empty($vehicles_needing_maintenance)): ?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Veículos Precisando de Manutenção Preventiva:</strong>
    <ul class="mb-0 mt-2">
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
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Alertas de manutenção vencida -->
<?php if (!empty($overdue_maintenances)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Manutenções Vencidas:</strong>
    <ul class="mb-0 mt-2">
        <?php foreach ($overdue_maintenances as $manutencao): ?>
            <li><?= escape($manutencao['veiculo_nome']) ?> - <?= escape($manutencao['tipo']) ?> (<?= formatDate($manutencao['data_manutencao']) ?>)</li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Cards de estatísticas -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Veículos Disponíveis
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($available_vehicles) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-truck text-primary" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total de Veículos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($vehicles) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-collection text-success" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Manutenções Vencidas
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($overdue_maintenances) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Deslocamentos Recentes
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($recent_trips) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-geo-alt text-info" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Veículos Disponíveis -->
<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Veículos Disponíveis</h6>
                <?php if ($auth->isAdmin()): ?>
                    <a href="veiculo-form.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i>Novo Veículo
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($vehicles)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-truck text-muted" style="font-size: 3rem;"></i>
                        <h5 class="text-muted mt-3">Nenhum veículo cadastrado</h5>
                        <p class="text-muted">Cadastre o primeiro veículo para começar a usar o sistema.</p>
                        <?php if ($auth->isAdmin()): ?>
                            <a href="veiculo-form.php" class="btn btn-primary">
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
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100 <?= $is_available ? 'border-success' : 'border-secondary' ?>" 
                                     style="cursor: <?= $is_available ? 'pointer' : 'default' ?>; opacity: <?= $is_available ? '1' : '0.6' ?>"
                                     <?= $is_available ? 'onclick="selectVehicle(' . $veiculo['id'] . ', \'' . escape($veiculo['nome']) . '\', ' . $veiculo['hodometro_atual'] . ')"' : '' ?>>
                                    <div class="card-body text-center">
                                        <?php if ($veiculo['foto']): ?>
                                            <img src="<?= UPLOADS_URL ?>/veiculos/<?= escape($veiculo['foto']) ?>" 
                                                 alt="<?= escape($veiculo['nome']) ?>"
                                                 class="img-fluid rounded mb-2" style="max-height: 100px;">
                                        <?php else: ?>
                                            <i class="bi bi-truck text-muted mb-2" style="font-size: 3rem;"></i>
                                        <?php endif; ?>
                                        
                                        <h6 class="card-title"><?= escape($veiculo['nome']) ?></h6>
                                        <p class="card-text">
                                            <span class="badge bg-secondary"><?= escape($veiculo['placa']) ?></span><br>
                                            <small class="text-muted"><?= number_format($veiculo['hodometro_atual']) ?> km</small>
                                        </p>
                                        
                                        <?php if ($is_available): ?>
                                            <span class="badge bg-success">Disponível</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Em uso</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($needs_maintenance): ?>
                                            <br><span class="badge bg-warning text-dark mt-1">Precisa manutenção</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Deslocamentos recentes -->
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Deslocamentos Recentes</h6>
                <a href="relatorios.php" class="btn btn-primary btn-sm">Ver Todos</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_trips)): ?>
                    <div class="text-center py-3">
                        <i class="bi bi-geo-alt text-muted" style="font-size: 2rem;"></i>
                        <p class="text-muted mt-2">Nenhum deslocamento recente</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_trips as $deslocamento): ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3">
                                <i class="bi bi-geo-alt text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold"><?= escape($deslocamento['destino']) ?></div>
                                <small class="text-muted">
                                    <?= escape($deslocamento['veiculo_nome']) ?> • 
                                    <?= formatDateTime($deslocamento['data_inicio']) ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <?php if ($deslocamento['km_retorno']): ?>
                                    <span class="badge bg-primary"><?= number_format($deslocamento['km_retorno'] - $deslocamento['km_saida']) ?> km</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para iniciar deslocamento -->
<div class="modal fade" id="tripModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Iniciar Novo Deslocamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form method="POST" id="startTripForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="start_trip">
                    <input type="hidden" name="veiculo_id" id="selected_vehicle_id">
                    
                    <div id="vehicleSelection">
                        <h6>Selecione um Veículo</h6>
                        <div class="row">
                            <?php foreach ($available_vehicles as $veiculo): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card vehicle-option" style="cursor: pointer;" 
                                         data-vehicle-id="<?= $veiculo['id'] ?>" 
                                         data-vehicle-name="<?= escape($veiculo['nome']) ?>" 
                                         data-vehicle-km="<?= $veiculo['hodometro_atual'] ?>">
                                        <div class="card-body text-center">
                                            <h6><?= escape($veiculo['nome']) ?></h6>
                                            <p class="mb-0">
                                                <span class="badge bg-secondary"><?= escape($veiculo['placa']) ?></span><br>
                                                <small><?= number_format($veiculo['hodometro_atual']) ?> km</small>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div id="tripForm" style="display: none;">
                        <div id="selectedVehicleInfo" class="alert alert-info"></div>
                        
                        <?php if ($auth->isAdmin()): ?>
                            <div class="mb-3">
                                <label for="usuario_id" class="form-label">Motorista *</label>
                                <select name="usuario_id" class="form-select" required>
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
                            <input type="text" name="destino" class="form-control" required placeholder="Para onde você está indo?">
                        </div>
                        
                        <div class="mb-3">
                            <label for="km_saida" class="form-label">KM de Saída *</label>
                            <input type="number" name="km_saida" id="km_saida" class="form-control" required min="0">
                            <div class="form-text">
                                Hodômetro atual: <span id="current_km">0</span> km
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="backBtn" class="btn btn-outline-secondary" onclick="backToSelection()" style="display: none;">Voltar</button>
                    <button type="submit" id="submitBtn" class="btn btn-primary" style="display: none;">Iniciar Deslocamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let selectedVehicleData = null;

function selectVehicle(id, name, km) {
    selectedVehicleData = { id, name, km };
    document.getElementById('selected_vehicle_id').value = id;
    document.getElementById('current_km').textContent = new Intl.NumberFormat('pt-BR').format(km);
    document.getElementById('km_saida').value = km;
    document.getElementById('km_saida').min = km;
    
    document.getElementById('selectedVehicleInfo').innerHTML = `
        <strong>Veículo selecionado:</strong> ${name}<br>
        <strong>Hodômetro atual:</strong> ${new Intl.NumberFormat('pt-BR').format(km)} km
    `;
    
    document.getElementById('vehicleSelection').style.display = 'none';
    document.getElementById('tripForm').style.display = 'block';
    document.getElementById('backBtn').style.display = 'inline-block';
    document.getElementById('submitBtn').style.display = 'inline-block';
}

function backToSelection() {
    document.getElementById('vehicleSelection').style.display = 'block';
    document.getElementById('tripForm').style.display = 'none';
    document.getElementById('backBtn').style.display = 'none';
    document.getElementById('submitBtn').style.display = 'none';
}

// Event listeners para seleção de veículo
document.querySelectorAll('.vehicle-option').forEach(option => {
    option.addEventListener('click', function() {
        const id = this.dataset.vehicleId;
        const name = this.dataset.vehicleName;
        const km = this.dataset.vehicleKm;
        selectVehicle(id, name, km);
    });
});

// Reset modal ao fechar
document.getElementById('tripModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('startTripForm').reset();
    backToSelection();
});
</script>

<?php require_once 'includes/footer.php'; ?>