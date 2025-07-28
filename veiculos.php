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

<!-- Header da página -->
<div class="dashboard-header">
    <div class="welcome-section">
        <h1 class="welcome-title">Veículos</h1>
        <p class="welcome-subtitle">Gerencie sua frota de veículos</p>
    </div>
    <div class="quick-actions">
        <a href="veiculo-form.php" class="btn-quick-action">
            <i class="bi bi-plus-circle"></i>
            <span>Novo Veículo</span>
        </a>
    </div>
</div>

<!-- Cards de estatísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="bi bi-truck"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= $fleet_stats['total_vehicles'] ?></div>
            <div class="stat-label">Total de Veículos</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="bi bi-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= $fleet_stats['available_vehicles'] ?></div>
            <div class="stat-label">Veículos Disponíveis</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="bi bi-play-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= $fleet_stats['active_trips'] ?></div>
            <div class="stat-label">Deslocamentos Ativos</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon info">
            <i class="bi bi-speedometer"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= number_format($fleet_stats['monthly_km']) ?></div>
            <div class="stat-label">KM Rodados (Mês)</div>
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

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <?= escape($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Lista de veículos -->
<div class="vehicles-section">
    <div class="section-header">
        <h2>Lista de Veículos</h2>
        <div class="section-actions">
            <button class="btn-refresh" onclick="refreshPage()">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    <?php if (empty($vehicles)): ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="bi bi-truck"></i>
            </div>
            <h3>Nenhum veículo cadastrado</h3>
            <p>Cadastre o primeiro veículo para começar a usar o sistema.</p>
            <a href="veiculo-form.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Cadastrar Primeiro Veículo
            </a>
        </div>
    <?php else: ?>
        <div class="vehicles-grid">
            <?php foreach ($vehicles as $veiculo): ?>
                <?php 
                $is_available = $fleet_manager ? $fleet_manager->isVehicleAvailable($veiculo['id']) : $veiculo['disponivel'];
                ?>
                <div class="vehicle-card-admin <?= $is_available ? 'available' : 'unavailable' ?>">
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
                            <span class="status-badge <?= $is_available ? 'available' : 'unavailable' ?>">
                                <?= $is_available ? 'Disponível' : 'Em uso' ?>
                            </span>
                        </div>
                    </div>
                    <div class="vehicle-info">
                        <h3><?= escape($veiculo['nome']) ?></h3>
                        <div class="vehicle-details">
                            <span class="plate"><?= escape($veiculo['placa']) ?></span>
                            <span class="km"><?= number_format($veiculo['hodometro_atual']) ?> km</span>
                        </div>
                        <?php if ($veiculo['troca_oleo_data']): ?>
                            <div class="maintenance-info">
                                <i class="bi bi-droplet"></i>
                                Último óleo: <?= formatDate($veiculo['troca_oleo_data']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($veiculo['observacoes']): ?>
                            <div class="vehicle-notes">
                                <?= escape(substr($veiculo['observacoes'], 0, 60)) ?>...
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="vehicle-actions">
                        <a href="veiculo-form.php?id=<?= $veiculo['id'] ?>" class="btn-action edit">
                            <i class="bi bi-pencil"></i>
                            Editar
                        </a>
                        <button type="button" class="btn-action delete" 
                                onclick="deleteVehicle(<?= $veiculo['id'] ?>, '<?= escape($veiculo['nome']) ?>')">
                            <i class="bi bi-trash"></i>
                            Excluir
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 1rem;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-icon.primary { background: var(--primary-color); }
.stat-icon.success { background: var(--accent-color); }
.stat-icon.warning { background: #f59e0b; }
.stat-icon.info { background: #3b82f6; }

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
}

.stat-label {
    color: var(--text-secondary);
    font-size: 0.875rem;
    font-weight: 500;
}

.vehicle-card-admin {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 1rem;
    overflow: hidden;
    transition: all 0.3s ease;
}

.vehicle-card-admin:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: var(--primary-color);
}

.vehicle-card-admin.unavailable {
    opacity: 0.7;
}

.maintenance-info {
    color: var(--text-secondary);
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin-top: 0.5rem;
}

.vehicle-notes {
    color: var(--text-secondary);
    font-size: 0.75rem;
    margin-top: 0.5rem;
    font-style: italic;
}

.vehicle-actions {
    padding: 1rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: 0.75rem;
}

.btn-action {
    flex: 1;
    padding: 0.5rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    background: none;
    color: var(--text-primary);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.2s;
    cursor: pointer;
}

.btn-action.edit:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
    background: color-mix(in srgb, var(--primary-color) 5%, transparent);
}

.btn-action.delete:hover {
    border-color: #ef4444;
    color: #ef4444;
    background: color-mix(in srgb, #ef4444 5%, transparent);
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .stat-card {
        padding: 1rem;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
}
</style>

<script>
function refreshPage() {
    const refreshBtn = document.querySelector('.btn-refresh');
    refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i>';
    
    setTimeout(() => {
        window.location.reload();
    }, 500);
}
</script>

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