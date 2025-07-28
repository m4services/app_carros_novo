<?php
class Config {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function get() {
        $stmt = $this->db->prepare("SELECT * FROM configuracoes LIMIT 1");
        $stmt->execute();
        $config = $stmt->fetch();
        
        if (!$config) {
            // Criar configuração padrão se não existir
            $stmt = $this->db->prepare("
                INSERT INTO configuracoes (logo, fonte, cor_primaria, cor_secundaria, cor_destaque, nome_empresa) 
                VALUES (NULL, 'Inter', '#007bff', '#6c757d', '#28a745', 'Sistema de Veículos')
            ");
            $stmt->execute();
            
            return $this->get();
        }
        
        return $config;
    }
    
    public function update($data, $logo = null) {
        try {
            $config = $this->get();
            $logo_name = $config['logo'];
            
            if ($logo && $logo['error'] === UPLOAD_ERR_OK) {
                // Remover logo antigo
                if ($logo_name) {
                    $old_logo = UPLOADS_PATH . '/logos/' . $logo_name;
                    if (file_exists($old_logo)) {
                        unlink($old_logo);
                    }
                }
                $logo_name = $this->uploadLogo($logo);
            }
            
            $stmt = $this->db->prepare("
                UPDATE configuracoes SET 
                    logo = ?, fonte = ?, cor_primaria = ?, cor_secundaria = ?, 
                    cor_destaque = ?, nome_empresa = ?
                WHERE id = ?
            ");
            
            return $stmt->execute([
                $logo_name,
                $data['fonte'],
                $data['cor_primaria'],
                $data['cor_secundaria'],
                $data['cor_destaque'],
                $data['nome_empresa'],
                $config['id']
            ]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function uploadLogo($logo) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/svg+xml'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($logo['type'], $allowed_types)) {
            throw new Exception('Tipo de arquivo não permitido');
        }
        
        if ($logo['size'] > $max_size) {
            throw new Exception('Arquivo muito grande');
        }
        
        $extension = pathinfo($logo['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . time() . '.' . $extension;
        $upload_path = UPLOADS_PATH . '/logos/' . $filename;
        
        if (move_uploaded_file($logo['tmp_name'], $upload_path)) {
            return $filename;
        }
        
        throw new Exception('Erro ao fazer upload do logo');
    }
}
?>