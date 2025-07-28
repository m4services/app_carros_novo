<?php
$page_title = 'Finalizar Deslocamento';
require_once 'includes/header.php';

$auth->requireLogin();

$trip_class = new Trip();
$active_trip = $auth->getActiveTrip();

// Se não há deslocamento ativo, redirecionar para dashboard
if (!$active_trip) {
    redirect('/dashboard.php');
}

$error = '';
$success = '';

// Processar finalização do deslocamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido.';
    } else {
        $km_retorno = (int)($_POST['km_retorno'] ?? 0);
        $observacoes = trim($_POST['observacoes'] ?? '');
        
        if (!$km_retorno) {
            $error = 'Por favor, informe a quilometragem de retorno.';
        } else if ($km_retorno < $active_trip['km_saida']) {
            $error = 'A quilometragem de retorno deve ser maior que a de saída.';
        } else {
            $trip_data = [
                'km_retorno' => $km_retorno,
                'observacoes' => $observacoes
            ];
            
            if ($trip_class->finishTrip($active_trip['id'], $trip_data)) {
                $success = 'Deslocamento finalizado com sucesso!';
                // Redirecionar após 2 segundos
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "dashboard.php";
                    }, 2000);
                </script>';
            } else {
                $error = 'Erro ao finalizar deslocamento.';
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Deslocamento Ativo - Finalização Obrigatória
                </h4>
            </div>
            <div class="card-body">
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
                        <div class="mt-2">
                            <div class="spinner-border spinner-border-sm me-2"></div>
                            Redirecionando para o dashboard...
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Atenção:</strong> Você possui um deslocamento ativo e deve finalizá-lo antes de acessar outras partes do sistema.
                </div>
                
                <!-- Informações do deslocamento ativo -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Informações do Deslocamento</h6>
                                <p class="card-text">
                                    <strong>Veículo:</strong> <?= escape($active_trip['veiculo_nome']) ?><br>
                                    <strong>Placa:</strong> <?= escape($active_trip['placa']) ?><br>
                                    <strong>Motorista:</strong> <?= escape($active_trip['motorista_nome']) ?><br>
                                    <strong>Destino:</strong> <?= escape($active_trip['destino']) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Dados de Saída</h6>
                                <p class="card-text">
                                    <strong>Data/Hora:</strong> <?= formatDateTime($active_trip['data_inicio']) ?><br>
                                    <strong>KM de Saída:</strong> <?= number_format($active_trip['km_saida']) ?> km<br>
                                    <strong>Status:</strong> <span class="badge bg-warning">Em Andamento</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Formulário de finalização -->
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="km_retorno" class="form-label">
                                    KM de Retorno <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" name="km_retorno" id="km_retorno" 
                                       required min="<?= $active_trip['km_saida'] ?>" 
                                       placeholder="Quilometragem atual do veículo"
                                       value="<?= escape($_POST['km_retorno'] ?? '') ?>">
                                <div class="form-text">
                                    Deve ser maior que <?= number_format($active_trip['km_saida']) ?> km
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, informe a quilometragem de retorno.
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">KM Rodados</label>
                                <input type="text" class="form-control" id="km_rodados" readonly 
                                       placeholder="Será calculado automaticamente">
                                <div class="form-text">
                                    Diferença entre KM de retorno e saída
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" id="observacoes" rows="4" 
                                  placeholder="Observações sobre o deslocamento (opcional)"><?= escape($_POST['observacoes'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i>
                                Deslocamento iniciado em <?= formatDateTime($active_trip['data_inicio']) ?>
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-lg">
                            <span class="loading spinner-border spinner-border-sm me-2"></span>
                            <i class="bi bi-check-circle me-2"></i>
                            Finalizar Deslocamento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Calcular KM rodados automaticamente
    document.getElementById('km_retorno').addEventListener('input', function() {
        const kmSaida = <?= $active_trip['km_saida'] ?>;
        const kmRetorno = parseInt(this.value) || 0;
        const kmRodados = kmRetorno > kmSaida ? kmRetorno - kmSaida : 0;
        
        document.getElementById('km_rodados').value = kmRodados > 0 ? kmRodados + ' km' : '';
    });
    
    // Confirmar finalização
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!confirm('Tem certeza que deseja finalizar este deslocamento? Esta ação não pode ser desfeita.')) {
            e.preventDefault();
        }
    });
    
    // Bloquear navegação
    window.addEventListener('beforeunload', function(e) {
        e.preventDefault();
        e.returnValue = 'Você possui um deslocamento ativo. Tem certeza que deseja sair?';
    });
</script>

<?php require_once 'includes/footer.php'; ?>