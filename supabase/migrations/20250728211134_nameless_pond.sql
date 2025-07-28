-- Atualização do banco de dados para novas funcionalidades

-- Tabela de notificações
CREATE TABLE IF NOT EXISTS notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    tipo ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
    url_acao VARCHAR(255) DEFAULT NULL,
    lida BOOLEAN DEFAULT FALSE,
    data_leitura DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_lida (usuario_id, lida),
    INDEX idx_created_at (created_at)
);

-- Melhorias na tabela de deslocamentos
ALTER TABLE deslocamentos 
ADD COLUMN IF NOT EXISTS velocidade_media DECIMAL(5,2) DEFAULT NULL COMMENT 'Velocidade média em km/h',
ADD COLUMN IF NOT EXISTS duracao_minutos INT DEFAULT NULL COMMENT 'Duração em minutos';

-- Melhorias na tabela de manutenções
ALTER TABLE manutencoes 
ADD COLUMN IF NOT EXISTS fornecedor VARCHAR(255) DEFAULT NULL COMMENT 'Fornecedor/Oficina',
ADD COLUMN IF NOT EXISTS numero_nota VARCHAR(50) DEFAULT NULL COMMENT 'Número da nota fiscal',
ADD COLUMN IF NOT EXISTS garantia_meses INT DEFAULT NULL COMMENT 'Garantia em meses';

-- Melhorias na tabela de veículos
ALTER TABLE veiculos 
ADD COLUMN IF NOT EXISTS marca VARCHAR(100) DEFAULT NULL COMMENT 'Marca do veículo',
ADD COLUMN IF NOT EXISTS modelo VARCHAR(100) DEFAULT NULL COMMENT 'Modelo do veículo',
ADD COLUMN IF NOT EXISTS ano_fabricacao YEAR DEFAULT NULL COMMENT 'Ano de fabricação',
ADD COLUMN IF NOT EXISTS cor VARCHAR(50) DEFAULT NULL COMMENT 'Cor do veículo',
ADD COLUMN IF NOT EXISTS combustivel ENUM('gasolina', 'etanol', 'flex', 'diesel', 'gnv', 'eletrico', 'hibrido') DEFAULT 'flex',
ADD COLUMN IF NOT EXISTS capacidade_tanque DECIMAL(5,2) DEFAULT NULL COMMENT 'Capacidade do tanque em litros',
ADD COLUMN IF NOT EXISTS consumo_medio DECIMAL(4,2) DEFAULT NULL COMMENT 'Consumo médio km/l';

-- Tabela de abastecimentos
CREATE TABLE IF NOT EXISTS abastecimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT NOT NULL,
    usuario_id INT NOT NULL,
    data_abastecimento DATETIME NOT NULL,
    km_abastecimento INT NOT NULL,
    litros DECIMAL(6,3) NOT NULL,
    valor_total DECIMAL(8,2) NOT NULL,
    valor_litro DECIMAL(5,3) NOT NULL,
    posto VARCHAR(255) DEFAULT NULL,
    tipo_combustivel ENUM('gasolina', 'etanol', 'diesel', 'gnv') NOT NULL,
    tanque_cheio BOOLEAN DEFAULT TRUE,
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_veiculo_data (veiculo_id, data_abastecimento),
    INDEX idx_data (data_abastecimento)
);

-- Tabela de multas
CREATE TABLE IF NOT EXISTS multas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT NOT NULL,
    usuario_id INT DEFAULT NULL,
    data_infracao DATE NOT NULL,
    local_infracao VARCHAR(255) NOT NULL,
    codigo_infracao VARCHAR(20) NOT NULL,
    descricao_infracao TEXT NOT NULL,
    valor DECIMAL(8,2) NOT NULL,
    pontos INT DEFAULT 0,
    status ENUM('pendente', 'paga', 'contestada', 'cancelada') DEFAULT 'pendente',
    data_vencimento DATE DEFAULT NULL,
    numero_auto VARCHAR(50) DEFAULT NULL,
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_veiculo_status (veiculo_id, status),
    INDEX idx_data_infracao (data_infracao),
    INDEX idx_status (status)
);

-- Tabela de documentos dos veículos
CREATE TABLE IF NOT EXISTS documentos_veiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT NOT NULL,
    tipo_documento ENUM('crlv', 'ipva', 'seguro', 'dpvat', 'licenciamento', 'outros') NOT NULL,
    numero_documento VARCHAR(100) DEFAULT NULL,
    data_vencimento DATE DEFAULT NULL,
    valor DECIMAL(8,2) DEFAULT NULL,
    arquivo VARCHAR(255) DEFAULT NULL,
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE,
    INDEX idx_veiculo_tipo (veiculo_id, tipo_documento),
    INDEX idx_vencimento (data_vencimento)
);

-- Melhorias na tabela de usuários
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS telefone VARCHAR(20) DEFAULT NULL COMMENT 'Telefone do usuário',
ADD COLUMN IF NOT EXISTS endereco TEXT DEFAULT NULL COMMENT 'Endereço completo',
ADD COLUMN IF NOT EXISTS data_nascimento DATE DEFAULT NULL COMMENT 'Data de nascimento',
ADD COLUMN IF NOT EXISTS categoria_cnh VARCHAR(10) DEFAULT 'B' COMMENT 'Categoria da CNH',
ADD COLUMN IF NOT EXISTS numero_cnh VARCHAR(20) DEFAULT NULL COMMENT 'Número da CNH';

-- Índices adicionais para performance
CREATE INDEX IF NOT EXISTS idx_deslocamentos_data_inicio ON deslocamentos(data_inicio);
CREATE INDEX IF NOT EXISTS idx_deslocamentos_data_fim ON deslocamentos(data_fim);
CREATE INDEX IF NOT EXISTS idx_manutencoes_data ON manutencoes(data_manutencao);
CREATE INDEX IF NOT EXISTS idx_usuarios_email ON usuarios(email);
CREATE INDEX IF NOT EXISTS idx_veiculos_placa ON veiculos(placa);

-- Atualizar dados existentes
UPDATE deslocamentos 
SET duracao_minutos = TIMESTAMPDIFF(MINUTE, data_inicio, data_fim)
WHERE data_fim IS NOT NULL AND duracao_minutos IS NULL;

UPDATE deslocamentos 
SET velocidade_media = CASE 
    WHEN duracao_minutos > 0 AND km_retorno > km_saida THEN 
        ROUND(((km_retorno - km_saida) / (duracao_minutos / 60)), 2)
    ELSE NULL 
END
WHERE velocidade_media IS NULL AND duracao_minutos IS NOT NULL;