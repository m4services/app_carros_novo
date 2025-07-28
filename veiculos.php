<?php
$page_title = 'Veículos';
require_once 'includes/header.php';

$auth->requireAdmin();

$vehicle = new Vehicle();
$fleet_manager = new FleetManager();
$error = '';
$success = '';

// Processar exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        if ($vehicle->delete($id)) {
            $success = 'Veículo excluído com sucesso!';
        } else {
            $error = 'Erro ao excluir veículo. Verifique se não há deslocamentos ativos.';
        }
    }
}

$vehicles = $vehicle->getAll();

// Obter estatísticas da frota
$fleet_stats = $fleet_manager ? $fleet_manager->getFleetStatistics() : [
    'total_vehicles' => 0,
    'available_vehicles' => 0,
    'active_trips' => 0,
    'monthly_km' => 0,
    'overdue_maintenances' => 0
];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Veículos</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="veiculo-form.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Novo Veículo
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?= escape($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <?= escape($success) ?>
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
                            Total de Veículos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $fleet_stats['total_vehicles'] ?></div>
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
                            Veículos Disponíveis
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $fleet_stats['available_vehicles'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
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
                            Deslocamentos Ativos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $fleet_stats['active_trips'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-play-circle text-warning" style="font-size: 2rem;"></i>
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
                            KM Rodados (Mês)
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($fleet_stats['monthly_km']) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-speedometer text-info" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Lista de Veículos</h6>
    </div>
    <div class="card-body">
        <?php if (empty($vehicles)): ?>
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="bi bi-truck text-muted" style="font-size: 4rem;"></i>
                </div>
                <h5 class="text-muted mb-3">Nenhum veículo cadastrado</h5>
                <p class="text-muted mb-4">Cadastre o primeiro veículo para começar a usar o sistema.</p>
                <a href="veiculo-form.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>Cadastrar Primeiro Veículo
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Nome</th>
                            <th>Placa</th>
                            <th>Hodômetro</th>
                            <th>Status</th>
                            <th>Última Manutenção</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicles as $veiculo): ?>
                            <?php 
                            $is_available = $fleet_manager ? $fleet_manager->isVehicleAvailable($veiculo['id']) : $veiculo['disponivel'];
                            ?>
                            <tr>
                                <td class="text-center">
                                    <?php if ($veiculo['foto']): ?>
                                        <img src="<?= UPLOADS_URL ?>/veiculos/<?= escape($veiculo['foto']) ?>" 
                                             alt="<?= escape($veiculo['nome']) ?>" 
                                             class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center" 
                                             style="width: 60px; height: 60px;">
                                            <i class="bi bi-truck text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= escape($veiculo['nome']) ?></div>
                                    <?php if ($veiculo['observacoes']): ?>
                                        <small class="text-muted"><?= escape(substr($veiculo['observacoes'], 0, 50)) ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= escape($veiculo['placa']) ?></span>
                                </td>
                                <td><?= number_format($veiculo['hodometro_atual']) ?> km</td>
                                <td>
                                    <?php if ($is_available): ?>
                                        <span class="badge bg-success">Disponível</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Em uso</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($veiculo['troca_oleo_data']): ?>
                                        <div>Óleo: <?= formatDate($veiculo['troca_oleo_data']) ?></div>
                                        <?php if ($veiculo['troca_oleo_km']): ?>
                                            <small class="text-muted"><?= number_format($veiculo['troca_oleo_km']) ?> km</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <small class="text-muted">Não informado</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="veiculo-form.php?id=<?= $veiculo['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteVehicle(<?= $veiculo['id'] ?>, '<?= escape($veiculo['nome']) ?>')" 
                                                title="Excluir">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o veículo <strong id="vehicleName"></strong>?</p>
                <p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="vehicleId">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteVehicle(id, name) {
    document.getElementById('vehicleId').value = id;
    document.getElementById('vehicleName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once 'includes/footer.php'; ?>