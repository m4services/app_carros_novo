<?php
class ReportGenerator {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Gera relatório detalhado de uso de veículos
     */
    public function generateUsageReport($filters = []) {
        try {
            $sql = "
                SELECT d.*, 
                       v.nome as veiculo_nome, v.placa,
                       u.nome as motorista_nome,
                       (d.km_retorno - d.km_saida) as km_rodados,
                       TIMESTAMPDIFF(MINUTE, d.data_inicio, d.data_fim) as duracao_minutos,
                       CASE 
                           WHEN d.data_fim IS NOT NULL THEN 
                               ROUND((d.km_retorno - d.km_saida) / (TIMESTAMPDIFF(MINUTE, d.data_inicio, d.data_fim) / 60), 2)
                           ELSE NULL 
                       END as velocidade_media
                FROM deslocamentos d
                JOIN veiculos v ON d.veiculo_id = v.id
                JOIN usuarios u ON d.usuario_id = u.id
                WHERE 1=1
            ";
            
            $params = [];
            
            if (!empty($filters['data_inicio'])) {
                $sql .= " AND DATE(d.data_inicio) >= ?";
                $params[] = $filters['data_inicio'];
            }
            
            if (!empty($filters['data_fim'])) {
                $sql .= " AND DATE(d.data_inicio) <= ?";
                $params[] = $filters['data_fim'];
            }
            
            if (!empty($filters['veiculo_id'])) {
                $sql .= " AND d.veiculo_id = ?";
                $params[] = $filters['veiculo_id'];
            }
            
            if (!empty($filters['usuario_id'])) {
                $sql .= " AND d.usuario_id = ?";
                $params[] = $filters['usuario_id'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND d.status = ?";
                $params[] = $filters['status'];
            }
            
            $sql .= " ORDER BY d.data_inicio DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Erro ao gerar relatório de uso: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Gera relatório de manutenções
     */
    public function generateMaintenanceReport($filters = []) {
        try {
            $sql = "
                SELECT m.*, v.nome as veiculo_nome, v.placa,
                       CASE 
                           WHEN m.status = 'agendada' AND m.data_manutencao < CURDATE() THEN 'vencida'
                           ELSE m.status 
                       END as status_real
                FROM manutencoes m
                JOIN veiculos v ON m.veiculo_id = v.id
                WHERE 1=1
            ";
            
            $params = [];
            
            if (!empty($filters['veiculo_id'])) {
                $sql .= " AND m.veiculo_id = ?";
                $params[] = $filters['veiculo_id'];
            }
            
            if (!empty($filters['tipo'])) {
                $sql .= " AND m.tipo LIKE ?";
                $params[] = '%' . $filters['tipo'] . '%';
            }
            
            if (!empty($filters['status'])) {
                if ($filters['status'] === 'vencida') {
                    $sql .= " AND m.status = 'agendada' AND m.data_manutencao < CURDATE()";
                } else {
                    $sql .= " AND m.status = ?";
                    $params[] = $filters['status'];
                }
            }
            
            if (!empty($filters['data_inicio'])) {
                $sql .= " AND m.data_manutencao >= ?";
                $params[] = $filters['data_inicio'];
            }
            
            if (!empty($filters['data_fim'])) {
                $sql .= " AND m.data_manutencao <= ?";
                $params[] = $filters['data_fim'];
            }
            
            $sql .= " ORDER BY m.data_manutencao DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Erro ao gerar relatório de manutenções: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Gera relatório de custos
     */
    public function generateCostReport($filters = []) {
        try {
            $sql = "
                SELECT v.id, v.nome, v.placa,
                       COUNT(m.id) as total_manutencoes,
                       COALESCE(SUM(m.valor), 0) as custo_manutencoes,
                       COUNT(d.id) as total_deslocamentos,
                       COALESCE(SUM(d.km_retorno - d.km_saida), 0) as km_rodados,
                       CASE 
                           WHEN SUM(d.km_retorno - d.km_saida) > 0 THEN 
                               ROUND(SUM(m.valor) / SUM(d.km_retorno - d.km_saida), 4)
                           ELSE 0 
                       END as custo_por_km
                FROM veiculos v
                LEFT JOIN manutencoes m ON v.id = m.veiculo_id AND m.status = 'realizada'
                LEFT JOIN deslocamentos d ON v.id = d.veiculo_id AND d.status = 'finalizado'
            ";
            
            $params = [];
            $where_conditions = [];
            
            if (!empty($filters['data_inicio'])) {
                $where_conditions[] = "(m.data_manutencao >= ? OR d.data_inicio >= ?)";
                $params[] = $filters['data_inicio'];
                $params[] = $filters['data_inicio'];
            }
            
            if (!empty($filters['data_fim'])) {
                $where_conditions[] = "(m.data_manutencao <= ? OR d.data_inicio <= ?)";
                $params[] = $filters['data_fim'];
                $params[] = $filters['data_fim'];
            }
            
            if (!empty($filters['veiculo_id'])) {
                $where_conditions[] = "v.id = ?";
                $params[] = $filters['veiculo_id'];
            }
            
            if (!empty($where_conditions)) {
                $sql .= " WHERE " . implode(" AND ", $where_conditions);
            }
            
            $sql .= " GROUP BY v.id, v.nome, v.placa ORDER BY custo_manutencoes DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Erro ao gerar relatório de custos: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Exporta relatório para CSV
     */
    public function exportToCSV($data, $filename, $headers) {
        try {
            $output = fopen('php://temp', 'r+');
            
            // Adicionar BOM para UTF-8
            fwrite($output, "\xEF\xBB\xBF");
            
            // Cabeçalhos
            fputcsv($output, $headers, ';');
            
            // Dados
            foreach ($data as $row) {
                $csv_row = [];
                foreach ($headers as $key => $header) {
                    $csv_row[] = $row[$key] ?? '';
                }
                fputcsv($output, $csv_row, ';');
            }
            
            rewind($output);
            $csv_content = stream_get_contents($output);
            fclose($output);
            
            // Headers para download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($csv_content));
            
            echo $csv_content;
            exit;
        } catch (Exception $e) {
            error_log('Erro ao exportar CSV: ' . $e->getMessage());
            return false;
        }
    }
}
?>