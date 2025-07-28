<?php
require_once '../config/config.php';

$auth = new Auth();
$auth->requireLogin();

$report_generator = new ReportGenerator();
$type = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'csv';

// Filtros
$filters = [
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? '',
    'veiculo_id' => (int)($_GET['veiculo_id'] ?? 0),
    'usuario_id' => (int)($_GET['usuario_id'] ?? 0),
    'status' => $_GET['status'] ?? ''
];

// Se não é admin, filtrar apenas seus dados
if (!$auth->isAdmin()) {
    $filters['usuario_id'] = $_SESSION['user_id'];
}

try {
    switch ($type) {
        case 'usage':
            $data = $report_generator->generateUsageReport($filters);
            $headers = [
                'data_inicio' => 'Data/Hora Início',
                'data_fim' => 'Data/Hora Fim',
                'motorista_nome' => 'Motorista',
                'veiculo_nome' => 'Veículo',
                'placa' => 'Placa',
                'destino' => 'Destino',
                'km_saida' => 'KM Saída',
                'km_retorno' => 'KM Retorno',
                'km_rodados' => 'KM Rodados',
                'duracao_minutos' => 'Duração (min)',
                'velocidade_media' => 'Velocidade Média',
                'observacoes' => 'Observações'
            ];
            $filename = 'relatorio_uso_' . date('Y-m-d') . '.csv';
            break;
            
        case 'maintenance':
            $data = $report_generator->generateMaintenanceReport($filters);
            $headers = [
                'data_manutencao' => 'Data',
                'veiculo_nome' => 'Veículo',
                'placa' => 'Placa',
                'tipo' => 'Tipo',
                'km_manutencao' => 'KM',
                'valor' => 'Valor',
                'status_real' => 'Status',
                'descricao' => 'Descrição'
            ];
            $filename = 'relatorio_manutencoes_' . date('Y-m-d') . '.csv';
            break;
            
        case 'costs':
            if (!$auth->isAdmin()) {
                throw new Exception('Acesso negado');
            }
            $data = $report_generator->generateCostReport($filters);
            $headers = [
                'nome' => 'Veículo',
                'placa' => 'Placa',
                'total_manutencoes' => 'Total Manutenções',
                'custo_manutencoes' => 'Custo Manutenções',
                'total_deslocamentos' => 'Total Deslocamentos',
                'km_rodados' => 'KM Rodados',
                'custo_por_km' => 'Custo por KM'
            ];
            $filename = 'relatorio_custos_' . date('Y-m-d') . '.csv';
            break;
            
        default:
            throw new Exception('Tipo de relatório inválido');
    }
    
    if (empty($data)) {
        throw new Exception('Nenhum dado encontrado para exportar');
    }
    
    $report_generator->exportToCSV($data, $filename, $headers);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>