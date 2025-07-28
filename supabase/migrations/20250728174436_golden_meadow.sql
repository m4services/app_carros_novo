-- Sistema de Controle de Veículos - Estrutura do Banco de Dados

-- Tabela de usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    documento VARCHAR(20) NOT NULL,
    validade_cnh DATE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    perfil ENUM('administrador', 'usuario') DEFAULT 'usuario',
    lembrar_token VARCHAR(255) DEFAULT NULL,
    ultimo_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de veículos
CREATE TABLE veiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    placa VARCHAR(10) NOT NULL UNIQUE,
    troca_oleo_data DATE DEFAULT NULL,
    troca_oleo_km INT DEFAULT NULL,
    hodometro_atual INT DEFAULT 0,
    alinhamento_data DATE DEFAULT NULL,
    alinhamento_km INT DEFAULT NULL,
    observacoes TEXT,
    foto VARCHAR(255) DEFAULT NULL,
    disponivel BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de deslocamentos
CREATE TABLE deslocamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    veiculo_id INT NOT NULL,
    destino VARCHAR(255) NOT NULL,
    km_saida INT NOT NULL,
    km_retorno INT DEFAULT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME DEFAULT NULL,
    observacoes TEXT,
    status ENUM('ativo', 'finalizado') DEFAULT 'ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE
);

-- Tabela de manutenções
CREATE TABLE manutencoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT NOT NULL,
    tipo VARCHAR(255) NOT NULL,
    data_manutencao DATE NOT NULL,
    km_manutencao INT NOT NULL,
    valor DECIMAL(10,2) DEFAULT 0.00,
    descricao TEXT,
    status ENUM('agendada', 'realizada', 'cancelada') DEFAULT 'agendada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE
);

-- Tabela de configurações (White Label)
CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    logo VARCHAR(255) DEFAULT NULL,
    fonte VARCHAR(255) DEFAULT 'Inter',
    cor_primaria VARCHAR(7) DEFAULT '#007bff',
    cor_secundaria VARCHAR(7) DEFAULT '#6c757d',
    cor_destaque VARCHAR(7) DEFAULT '#28a745',
    nome_empresa VARCHAR(255) DEFAULT 'Sistema de Veículos',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de logs do sistema
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT DEFAULT NULL,
    acao VARCHAR(255) NOT NULL,
    descricao TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Inserir configuração padrão
INSERT INTO configuracoes (logo, fonte, cor_primaria, cor_secundaria, cor_destaque, nome_empresa) 
VALUES (NULL, 'Inter', '#007bff', '#6c757d', '#28a745', 'Sistema de Veículos');

-- Inserir usuário administrador padrão (senha: admin123)
INSERT INTO usuarios (nome, documento, validade_cnh, email, senha, perfil) 
VALUES ('Administrador', '000.000.000-00', '2030-12-31', 'admin@sistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrador');

-- Índices para melhor performance
CREATE INDEX idx_usuarios_email ON usuarios(email);
CREATE INDEX idx_deslocamentos_usuario ON deslocamentos(usuario_id);
CREATE INDEX idx_deslocamentos_veiculo ON deslocamentos(veiculo_id);
CREATE INDEX idx_deslocamentos_status ON deslocamentos(status);
CREATE INDEX idx_manutencoes_veiculo ON manutencoes(veiculo_id);
CREATE INDEX idx_logs_usuario ON logs(usuario_id);