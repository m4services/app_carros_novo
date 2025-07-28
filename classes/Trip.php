<?php
class Trip {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function startTrip($data) {
        try {
            $this->db->beginTransaction();
            
            // Verificar se veículo está disponível
            $stmt = $this->db->prepare("
                SELECT v.id FROM veiculos v
                LEFT JOIN deslocamentos d ON v.id = d.veiculo_id AND d.status = 'ativo'
                WHERE v.id = ? AND v.disponivel = 1 AND d.id IS NULL
            ");
            $stmt->execute([$data['veiculo_id']]);
            
            if (!$stmt->fetch()) {
                $this->db->rollBack();
                throw new Exception('Veículo não disponível ou já em uso');
            }
            
            // Verificar se usuário já tem deslocamento ativo
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM deslocamentos WHERE usuario_id = ? AND status = 'ativo'");
            $stmt->execute([$data['usuario_id']]);
            
            if ($stmt->fetchColumn() > 0) {
                $this->db->rollBack();
                throw new Exception('Usuário já possui um deslocamento ativo');
            }
            
            // Validar KM de saída
            $stmt = $this->db->prepare("SELECT hodometro_atual FROM veiculos WHERE id = ?");
            $stmt->execute([$data['veiculo_id']]);
            $hodometro_atual = $stmt->fetchColumn();
            
            if ($data['km_saida'] < $hodometro_atual) {
                $this->db->rollBack();
                throw new Exception('KM de saída não pode ser menor que o hodômetro atual do veículo (' . number_format($hodometro_atual) . ' km)');
            }
            
            // Criar deslocamento
            $stmt = $this->db->prepare("
                INSERT INTO deslocamentos (usuario_id, veiculo_id, destino, km_saida, data_inicio, status) 
                VALUES (?, ?, ?, ?, ?, 'ativo')
            ");
            
            $result = $stmt->execute([
                $data['usuario_id'],
                $data['veiculo_id'],
                $data['destino'],
                $data['km_saida'],
                date('Y-m-d H:i:s')
            ]);
            
            if ($result) {
                $this->db->commit();
                return $this->db->lastInsertId();
            }
            
            $this->db->rollBack();
            throw new Exception('Erro ao criar deslocamento');
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function finishTrip($trip_id, $data) {
        try {
            $this->db->beginTransaction();
            
            // Verificar se deslocamento existe e está ativo
            $stmt = $this->db->prepare("SELECT * FROM deslocamentos WHERE id = ? AND status = 'ativo'");
            $stmt->execute([$trip_id]);
            $trip = $stmt->fetch();
            
            if (!$trip) {
                $this->db->rollBack();
                throw new Exception('Deslocamento não encontrado ou já finalizado');
            }
            
            // Validar KM de retorno
            if ($data['km_retorno'] <= $trip['km_saida']) {
                $this->db->rollBack();
                throw new Exception('KM de retorno deve ser maior que KM de saída (' . number_format($trip['km_saida']) . ' km)');
            }
            
            // Atualizar deslocamento
            $stmt = $this->db->prepare("
                UPDATE deslocamentos SET 
                    km_retorno = ?, 
                    observacoes = ?, 
                    data_fim = ?, 
                    status = 'finalizado' 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $data['km_retorno'],
                $data['observacoes'] ?? null,
                date('Y-m-d H:i:s'),
                $trip_id
            ]);
            
            if ($result) {
                // Atualizar hodômetro do veículo
                $km_rodados = $data['km_retorno'] - $trip['km_saida'];
                $stmt = $this->db->prepare("
                    UPDATE veiculos SET hodometro_atual = ? WHERE id = ?
                ");
                $stmt->execute([$data['km_retorno'], $trip['veiculo_id']]);
                
                $this->db->commit();
                return true;
            }
            
            $this->db->rollBack();
            throw new Exception('Erro ao finalizar deslocamento');
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function getTrips($user_id = null, $filters = []) {
        $sql = "
            SELECT d.*, v.nome as veiculo_nome, v.placa, u.nome as motorista_nome
            FROM deslocamentos d
            JOIN veiculos v ON d.veiculo_id = v.id
            JOIN usuarios u ON d.usuario_id = u.id
        ";
        
        $where = [];
        $params = [];
        
        if ($user_id) {
            $where[] = "d.usuario_id = ?";
            $params[] = $user_id;
        }
        
        if (!empty($filters['data_inicio'])) {
            $where[] = "DATE(d.data_inicio) >= ?";
            $params[] = $filters['data_inicio'];
        }
        
        if (!empty($filters['data_fim'])) {
            $where[] = "DATE(d.data_inicio) <= ?";
            $params[] = $filters['data_fim'];
        }
        
        if (!empty($filters['veiculo_id'])) {
            $where[] = "d.veiculo_id = ?";
            $params[] = $filters['veiculo_id'];
        }
        
        if (!empty($filters['usuario_id'])) {
            $where[] = "d.usuario_id = ?";
            $params[] = $filters['usuario_id'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "d.status = ?";
            $params[] = $filters['status'];
        }
        
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY d.data_inicio DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT d.*, v.nome as veiculo_nome, v.placa, u.nome as motorista_nome
            FROM deslocamentos d
            JOIN veiculos v ON d.veiculo_id = v.id
            JOIN usuarios u ON d.usuario_id = u.id
            WHERE d.id = ?
        ");
        $stmt->execute([$id]);
        
        return $stmt->fetch();
    }
}
?>