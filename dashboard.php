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
                    // Redirecionar imediatamente sem mostrar mensagem
                    echo '<script>window.location.href = "finalizar-deslocamento.php";</script>';
                    exit;
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header da Dashboard -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Olá, <?= escape($_SESSION['user_name'] ?? 'Usuário') ?>!</h1>
                <p class="text-gray-600 mt-1">Bem-vindo ao sistema de controle de veículos</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <button onclick="openTripModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium flex items-center space-x-2 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span>Novo Deslocamento</span>
                </button>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="ml-3">
                        <p class="text-red-800"><?= escape($error) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Alertas de manutenção preventiva -->
        <?php if (!empty($vehicles_needing_maintenance)): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <div class="flex">
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div class="ml-3">
                    <h3 class="text-yellow-800 font-medium">Veículos Precisando de Manutenção Preventiva</h3>
                    <div class="mt-2 text-yellow-700">
                        <?php foreach ($vehicles_needing_maintenance as $veiculo): ?>
                            <div class="mb-1">
                                <strong><?= escape($veiculo['nome']) ?></strong>
                                <?php if ($veiculo['days_since_oil_change'] > 180): ?>
                                    - Troca de óleo há <?= $veiculo['days_since_oil_change'] ?> dias
                                <?php endif; ?>
                                <?php if ($veiculo['km_since_oil_change'] > 10000): ?>
                                    - Troca de óleo há <?= number_format($veiculo['km_since_oil_change']) ?> km
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Alertas de manutenção vencida -->
        <?php if (!empty($overdue_maintenances)): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div class="flex">
                <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div class="ml-3">
                    <h3 class="text-red-800 font-medium">Manutenções Vencidas</h3>
                    <div class="mt-2 text-red-700">
                        <?php foreach ($overdue_maintenances as $manutencao): ?>
                            <div><?= escape($manutencao['veiculo_nome']) ?> - <?= escape($manutencao['tipo']) ?> (<?= formatDate($manutencao['data_manutencao']) ?>)</div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Veículos Disponíveis -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Veículos Disponíveis</h2>
                <div class="flex items-center space-x-3">
                    <button onclick="refreshVehicles()" class="p-2 text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                    <?php if ($auth->isAdmin()): ?>
                        <a href="veiculo-form.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            Novo Veículo
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($vehicles)): ?>
                <div class="text-center py-12">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhum veículo cadastrado</h3>
                    <p class="text-gray-600 mb-6">Cadastre o primeiro veículo para começar a usar o sistema.</p>
                    <?php if ($auth->isAdmin()): ?>
                        <a href="veiculo-form.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                            Cadastrar Primeiro Veículo
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="vehiclesGrid">
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
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow <?= $is_available ? 'cursor-pointer' : 'opacity-60' ?>" 
                             <?= $is_available ? 'onclick="selectVehicle(' . $veiculo['id'] . ', \'' . escape($veiculo['nome']) . '\', ' . $veiculo['hodometro_atual'] . ')"' : '' ?>>
                            <div class="relative h-48">
                                <?php if ($veiculo['foto']): ?>
                                    <img src="<?= UPLOADS_URL ?>/veiculos/<?= escape($veiculo['foto']) ?>" 
                                         alt="<?= escape($veiculo['nome']) ?>"
                                         class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute top-3 right-3">
                                    <?php if ($is_available): ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?= $needs_maintenance ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?>">
                                            <?= $needs_maintenance ? 'Manutenção' : 'Disponível' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                            Em uso
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="p-4">
                                <h3 class="font-semibold text-gray-900 mb-2"><?= escape($veiculo['nome']) ?></h3>
                                <div class="flex justify-between items-center text-sm text-gray-600">
                                    <span class="bg-gray-100 px-2 py-1 rounded font-mono"><?= escape($veiculo['placa']) ?></span>
                                    <span><?= number_format($veiculo['hodometro_atual']) ?> km</span>
                                </div>
                                <?php if ($needs_maintenance): ?>
                                    <div class="mt-2 text-xs text-yellow-600 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        Precisa de manutenção
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Deslocamentos recentes -->
        <?php if (!empty($recent_trips)): ?>
        <div class="mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Deslocamentos Recentes</h2>
                <a href="relatorios.php" class="text-blue-600 hover:text-blue-700 font-medium">Ver Todos</a>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <?php foreach ($recent_trips as $index => $deslocamento): ?>
                    <div class="p-4 <?= $index > 0 ? 'border-t border-gray-200' : '' ?> hover:bg-gray-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900"><?= escape($deslocamento['destino']) ?></div>
                                    <div class="text-sm text-gray-600">
                                        <?= escape($deslocamento['veiculo_nome']) ?> • 
                                        <?= formatDateTime($deslocamento['data_inicio']) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-medium text-gray-900">
                                    <?= $deslocamento['km_retorno'] ? number_format($deslocamento['km_retorno'] - $deslocamento['km_saida']) . ' km' : '-' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para iniciar deslocamento -->
<div id="tripModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Iniciar Novo Deslocamento</h3>
                    <button onclick="closeTripModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <form method="POST" id="startTripForm" class="p-6">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="start_trip">
                <input type="hidden" name="veiculo_id" id="selected_vehicle_id">
                
                <div id="vehicleSelection">
                    <h4 class="font-medium text-gray-900 mb-4">Selecione um Veículo</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php foreach ($available_vehicles as $veiculo): ?>
                            <div class="border border-gray-200 rounded-lg p-4 cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition-colors vehicle-option" 
                                 data-vehicle-id="<?= $veiculo['id'] ?>" 
                                 data-vehicle-name="<?= escape($veiculo['nome']) ?>" 
                                 data-vehicle-km="<?= $veiculo['hodometro_atual'] ?>">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                        <?php if ($veiculo['foto']): ?>
                                            <img src="<?= UPLOADS_URL ?>/veiculos/<?= escape($veiculo['foto']) ?>" 
                                                 alt="<?= escape($veiculo['nome']) ?>"
                                                 class="w-full h-full object-cover rounded-lg">
                                        <?php else: ?>
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900"><?= escape($veiculo['nome']) ?></div>
                                        <div class="text-sm text-gray-600"><?= escape($veiculo['placa']) ?> • <?= number_format($veiculo['hodometro_atual']) ?> km</div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="tripForm" class="hidden">
                    <div id="selectedVehicleInfo" class="bg-blue-50 rounded-lg p-4 mb-6"></div>
                    
                    <?php if ($auth->isAdmin()): ?>
                        <div class="mb-4">
                            <label for="usuario_id" class="block text-sm font-medium text-gray-700 mb-2">Motorista *</label>
                            <select name="usuario_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
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
                    
                    <div class="mb-4">
                        <label for="destino" class="block text-sm font-medium text-gray-700 mb-2">Destino *</label>
                        <input type="text" name="destino" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required placeholder="Para onde você está indo?">
                    </div>
                    
                    <div class="mb-6">
                        <label for="km_saida" class="block text-sm font-medium text-gray-700 mb-2">KM de Saída *</label>
                        <input type="number" name="km_saida" id="km_saida" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required min="0">
                        <p class="text-sm text-gray-600 mt-1">
                            Hodômetro atual: <span id="current_km">0</span> km
                        </p>
                    </div>
                    
                    <div class="flex justify-between">
                        <button type="button" onclick="backToSelection()" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            Voltar
                        </button>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Iniciar Deslocamento
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let selectedVehicleData = null;

function openTripModal() {
    document.getElementById('tripModal').classList.remove('hidden');
}

function closeTripModal() {
    document.getElementById('tripModal').classList.add('hidden');
    resetModal();
}

function resetModal() {
    document.getElementById('vehicleSelection').classList.remove('hidden');
    document.getElementById('tripForm').classList.add('hidden');
    document.getElementById('startTripForm').reset();
    selectedVehicleData = null;
}

function selectVehicle(id, name, km) {
    selectedVehicleData = { id, name, km };
    document.getElementById('selected_vehicle_id').value = id;
    document.getElementById('current_km').textContent = new Intl.NumberFormat('pt-BR').format(km);
    document.getElementById('km_saida').value = km;
    document.getElementById('km_saida').min = km;
    
    document.getElementById('selectedVehicleInfo').innerHTML = `
        <div class="flex items-center space-x-3">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <div class="font-medium text-gray-900">${name}</div>
                <div class="text-sm text-gray-600">Hodômetro: ${new Intl.NumberFormat('pt-BR').format(km)} km</div>
            </div>
        </div>
    `;
    
    document.getElementById('vehicleSelection').classList.add('hidden');
    document.getElementById('tripForm').classList.remove('hidden');
    
    if (!document.getElementById('tripModal').classList.contains('hidden')) {
        // Modal já está aberto, apenas mudou de tela
    } else {
        openTripModal();
    }
}

function backToSelection() {
    document.getElementById('vehicleSelection').classList.remove('hidden');
    document.getElementById('tripForm').classList.add('hidden');
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

// Refresh de veículos
function refreshVehicles() {
    const refreshBtn = document.querySelector('button[onclick="refreshVehicles()"]');
    const icon = refreshBtn.querySelector('svg');
    icon.classList.add('animate-spin');
    
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

// Fechar modal ao clicar fora
document.getElementById('tripModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeTripModal();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>