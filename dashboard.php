<?php
$page_title = 'Dashboard';
require_once 'includes/header.php';

$auth->requireLogin();

$vehicle = new Vehicle();
$maintenance = new Maintenance();
$trip = new Trip();

$vehicles = $vehicle->getAll();
$available_vehicles = $vehicle->getAvailable();
$overdue_maintenances = $maintenance->getOverdue();
$upcoming_maintenances = $maintenance->getUpcoming();
$recent_trips = $trip->getTrips($auth->isAdmin() ? null : $_SESSION['user_id'], ['status' => 'finalizado']);
$recent_trips = array_slice($recent_trips, 0, 5);

$message = '';
$error = '';

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
        <div class="card border-left-primary">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total de Veículos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($vehicles) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-truck text-primary" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Veículos Disponíveis
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($available_vehicles) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning">
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
        <div class="card border-left-info">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Próximas Manutenções
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($upcoming_maintenances) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-calendar-event text-info" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
        <h5 class="mb-0"><i class="bi bi-truck me-2"></i>Veículos</h5>
    </div>
    <div class="card-body">
        <?php if (empty($vehicles)): ?>
            <div class="text-center py-4">
                <i class="bi bi-truck text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2">Nenhum veículo cadastrado.</p>
                <?php if ($auth->isAdmin()): ?>
                    <a href="veiculo-form.php" class="btn btn-primary">Cadastrar Primeiro Veículo</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($vehicles as $veiculo): ?>
                    <?php 
                    $is_available = $vehicle->isAvailable($veiculo['id']);
                    ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                        <div class="card vehicle-card <?= $is_available ? '' : 'unavailable' ?>" 
                             <?= $is_available ? 'data-bs-toggle="modal" data-bs-target="#startTripModal" data-vehicle-id="' . $veiculo['id'] . '" data-vehicle-name="' . escape($veiculo['nome']) . '"' : '' ?>>
                            <div class="position-relative">
                                <?php if ($veiculo['foto']): ?>
                                    <img src="<?= UPLOADS_URL ?>/veiculos/<?= escape($veiculo['foto']) ?>" class="card-img-top" alt="<?= escape($veiculo['nome']) ?>" style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <i class="bi bi-truck text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge <?= $is_available ? 'bg-success' : 'bg-danger' ?>">
                                        <?= $is_available ? 'Disponível' : 'Em uso' ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <h6 class="card-title"><?= escape($veiculo['nome']) ?></h6>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <i class="bi bi-hash me-1"></i><?= escape($veiculo['placa']) ?><br>
                                        <i class="bi bi-speedometer me-1"></i><?= number_format($veiculo['hodometro_atual']) ?> km
                                    </small>
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
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Deslocamentos Recentes</h5>
        <a href="relatorios.php" class="btn btn-sm btn-outline-primary">Ver Todos</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
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
                            <td><?= $deslocamento['km_retorno'] ? number_format($deslocamento['km_retorno'] - $deslocamento['km_saida']) . ' km' : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal para iniciar deslocamento -->
<div class="modal fade" id="startTripModal" tabindex="-1" aria-labelledby="startTripModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="startTripModalLabel">
                    <i class="bi bi-play-circle me-2"></i>Iniciar Deslocamento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
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
                            <label for="usuario_id" class="form-label">Motorista <span class="text-danger">*</span></label>
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
                            <div class="invalid-feedback">Por favor, selecione o motorista.</div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="destino" class="form-label">Destino <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="destino" id="destino" required 
                               placeholder="Para onde será o deslocamento?">
                        <div class="invalid-feedback">Por favor, informe o destino.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="km_saida" class="form-label">KM de Saída <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="km_saida" id="km_saida" required min="0" 
                               placeholder="Quilometragem atual do veículo">
                        <div class="invalid-feedback">Por favor, informe a quilometragem de saída.</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Atenção:</strong> Após iniciar o deslocamento, você será redirecionado para a tela de finalização e não poderá acessar outras partes do sistema até finalizar o deslocamento.
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
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
    document.getElementById('startTripModal').addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const vehicleId = button.getAttribute('data-vehicle-id');
        const vehicleName = button.getAttribute('data-vehicle-name');
        
        document.getElementById('modal_veiculo_id').value = vehicleId;
        document.getElementById('modal_veiculo_nome').value = vehicleName;
    });
</script>

<?php require_once 'includes/footer.php'; ?>