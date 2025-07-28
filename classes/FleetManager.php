<?php
class FleetManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Verifica se um veículo está realmente disponível
     */
    public function isVehicleAvailable($vehicle_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT v.id, v.disponivel,
                       COUNT(d.id) as active_trips
                FROM veiculos v
                LEFT JOIN deslocamentos d ON v.id = d.veiculo_id AND d.status = 'ativo'
                WHERE v.id = ? AND v.disponivel = 1
                GROUP BY v.id
                HAVING active_trips = 0
            ");
            $stmt->execute([$vehicle_id]);
            
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            error_log('Erro ao verificar disponibilidade do veículo: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém estatísticas da frota
     */
    public function getFleetStatistics() {
        try {
            $stats = [];
            
            // Total de veículos
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM veiculos");
            $stmt->execute();
            $stats['total_vehicles'] = $stmt->fetchColumn();
            
            // Veículos disponíveis
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as available 
                FROM veiculos v
                LEFT JOIN deslocamentos d ON v.id = d.veiculo_id AND d.status = 'ativo'
                WHERE v.disponivel = 1 AND d.id IS NULL
            ");
            $stmt->execute();
            $stats['available_vehicles'] = $stmt->fetchColumn();
            
            // Deslocamentos ativos
            $stmt = $this->db->prepare("SELECT COUNT(*) as active FROM deslocamentos WHERE status = 'ativo'");
            $stmt->execute();
            $stats['active_trips'] = $stmt->fetchColumn();
            
            // KM total rodados no mês
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(km_retorno - km_saida), 0) as total_km
                FROM deslocamentos 
                WHERE status = 'finalizado' 
                AND MONTH(data_inicio) = MONTH(CURRENT_DATE())
                AND YEAR(data_inicio) = YEAR(CURRENT_DATE())
            ");
            $stmt->execute();
            $stats['monthly_km'] = $stmt->fetchColumn();
            
            // Manutenções vencidas
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as overdue
                FROM manutencoes 
                WHERE data_manutencao < CURDATE() AND status = 'agendada'
            ");
            $stmt->execute();
            $stats['overdue_maintenances'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (Exception $e) {
            error_log('Erro ao obter estatísticas da frota: ' . $e->getMessage());
            return [
                'total_vehicles' => 0,
                'available_vehicles' => 0,
                'active_trips' => 0,
                'monthly_km' => 0,
                'overdue_maintenances' => 0
            ];
        }
    }
    
    /**
     * Obtém veículos que precisam de manutenção
     */
    public function getVehiclesNeedingMaintenance() {
        try {
            $stmt = $this->db->prepare("
                SELECT v.*, 
                       DATEDIFF(CURDATE(), v.troca_oleo_data) as days_since_oil_change,
                       (v.hodometro_atual - v.troca_oleo_km) as km_since_oil_change,
                       DATEDIFF(CURDATE(), v.alinhamento_data) as days_since_alignment,
                       (v.hodometro_atual - v.alinhamento_km) as km_since_alignment
                FROM veiculos v
                WHERE (
                    (v.troca_oleo_data IS NOT NULL AND DATEDIFF(CURDATE(), v.troca_oleo_data) > 180) OR
                    (v.troca_oleo_km IS NOT NULL AND (v.hodometro_atual - v.troca_oleo_km) > 10000) OR
                    (v.alinhamento_data IS NOT NULL AND DATEDIFF(CURDATE(), v.alinhamento_data) > 365) OR
                    (v.alinhamento_km IS NOT NULL AND (v.hodometro_atual - v.alinhamento_km) > 20000)
                )
                ORDER BY v.nome
            ");
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Erro ao obter veículos que precisam de manutenção: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calcula eficiência de combustível por veículo
     */
    public function getFuelEfficiency($vehicle_id = null, $days = 30) {
        try {
            $sql = "
                SELECT v.id, v.nome, v.placa,
                       COUNT(d.id) as total_trips,
                       COALESCE(SUM(d.km_retorno - d.km_saida), 0) as total_km,
                       COALESCE(AVG(d.km_retorno - d.km_saida), 0) as avg_km_per_trip
                FROM veiculos v
                LEFT JOIN deslocamentos d ON v.id = d.veiculo_id 
                    AND d.status = 'finalizado'
                    AND d.data_inicio >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            ";
            
            $params = [$days];
            
            if ($vehicle_id) {
                $sql .= " WHERE v.id = ?";
                $params[] = $vehicle_id;
            }
            
            $sql .= " GROUP BY v.id, v.nome, v.placa ORDER BY total_km DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Erro ao calcular eficiência de combustível: ' . $e->getMessage());
            return [];
        }
    }
}
?>