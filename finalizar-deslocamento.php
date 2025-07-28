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
        } else if ($km_retorno <= $active_trip['km_saida']) {
            $error = 'A quilometragem de retorno deve ser maior que a de saída.';
        } else {
            try {
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
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

// Calcular duração
$inicio = new DateTime($active_trip['data_inicio']);
$agora = new DateTime();
$duracao = $agora->diff($inicio);
$duracao_texto = '';
if ($duracao->h > 0) {
    $duracao_texto = $duracao->h . 'h ' . $duracao->i . 'min';
} else {
    $duracao_texto = $duracao->i . 'min';
}
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Deslocamento Ativo</h1>
            <p class="text-gray-600">Finalize seu deslocamento para continuar usando o sistema</p>
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
        
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="ml-3">
                        <p class="text-green-800"><?= escape($success) ?></p>
                        <div class="mt-2 flex items-center text-green-700">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Redirecionando para o dashboard...
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Informações do deslocamento -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Informações do Deslocamento</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="flex items-center space-x-3 mb-3">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <div class="text-sm text-gray-600">Veículo</div>
                                <div class="font-medium text-gray-900"><?= escape($active_trip['veiculo_nome']) ?></div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3 mb-3">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            <div>
                                <div class="text-sm text-gray-600">Placa</div>
                                <div class="font-medium text-gray-900"><?= escape($active_trip['placa']) ?></div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <div>
                                <div class="text-sm text-gray-600">Motorista</div>
                                <div class="font-medium text-gray-900"><?= escape($active_trip['motorista_nome']) ?></div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center space-x-3 mb-3">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <div>
                                <div class="text-sm text-gray-600">Destino</div>
                                <div class="font-medium text-gray-900"><?= escape($active_trip['destino']) ?></div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3 mb-3">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <div class="text-sm text-gray-600">Iniciado em</div>
                                <div class="font-medium text-gray-900"><?= formatDateTime($active_trip['data_inicio']) ?></div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <div>
                                <div class="text-sm text-gray-600">Duração</div>
                                <div class="font-medium text-gray-900" id="trip_duration"><?= $duracao_texto ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulário de finalização -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-6">Finalizar Deslocamento</h2>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="km_retorno" class="block text-sm font-medium text-gray-700 mb-2">
                                KM de Retorno *
                            </label>
                            <input type="number" name="km_retorno" id="km_retorno" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                   required min="<?= $active_trip['km_saida'] ?>" 
                                   placeholder="Quilometragem atual do veículo"
                                   value="<?= escape($_POST['km_retorno'] ?? '') ?>">
                            <p class="text-sm text-gray-600 mt-1">
                                Deve ser maior que <?= number_format($active_trip['km_saida']) ?> km
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                KM Rodados
                            </label>
                            <input type="text" id="km_rodados" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" 
                                   readonly placeholder="Será calculado automaticamente">
                            <p class="text-sm text-gray-600 mt-1">
                                Diferença entre KM de retorno e saída
                            </p>
                        </div>
                    </div>
                    
                    <div>
                        <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-2">
                            Observações
                        </label>
                        <textarea name="observacoes" id="observacoes" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                  placeholder="Observações sobre o deslocamento (opcional)"><?= escape($_POST['observacoes'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="flex justify-between items-center pt-4">
                        <div class="text-sm text-gray-600">
                            <div>KM de saída: <span class="font-medium"><?= number_format($active_trip['km_saida']) ?> km</span></div>
                        </div>
                        
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg font-medium transition-colors">
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
    
    const kmRodadosField = document.getElementById('km_rodados');
    if (kmRodados > 0) {
        kmRodadosField.value = kmRodados.toLocaleString('pt-BR') + ' km';
        kmRodadosField.classList.remove('bg-gray-50');
        kmRodadosField.classList.add('bg-green-50', 'text-green-800', 'font-medium');
    } else {
        kmRodadosField.value = '';
        kmRodadosField.classList.add('bg-gray-50');
        kmRodadosField.classList.remove('bg-green-50', 'text-green-800', 'font-medium');
    }
    
    // Validação visual
    if (kmRetorno > 0 && kmRetorno <= kmSaida) {
        this.classList.add('border-red-300', 'focus:ring-red-500', 'focus:border-red-500');
        this.classList.remove('border-gray-300', 'focus:ring-blue-500', 'focus:border-blue-500');
    } else {
        this.classList.remove('border-red-300', 'focus:ring-red-500', 'focus:border-red-500');
        this.classList.add('border-gray-300', 'focus:ring-blue-500', 'focus:border-blue-500');
    }
});

// Atualizar duração do deslocamento
function updateTripDuration() {
    const startTime = new Date('<?= date('c', strtotime($active_trip['data_inicio'])) ?>');
    const now = new Date();
    const diff = now - startTime;
    
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    
    const durationElement = document.getElementById('trip_duration');
    if (durationElement) {
        if (hours > 0) {
            durationElement.textContent = `${hours}h ${minutes}min`;
        } else {
            durationElement.textContent = `${minutes}min`;
        }
    }
}

// Atualizar duração a cada minuto
updateTripDuration();
setInterval(updateTripDuration, 60000);

// Auto-focus no campo KM de retorno
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        const kmRetornoField = document.getElementById('km_retorno');
        if (kmRetornoField) {
            kmRetornoField.focus();
        }
    }, 500);
});

// Bloquear navegação
window.addEventListener('beforeunload', function(e) {
    if (!document.querySelector('.bg-green-50')) {
        e.preventDefault();
        e.returnValue = 'Você possui um deslocamento ativo. Tem certeza que deseja sair?';
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>