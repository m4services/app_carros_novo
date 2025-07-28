<?php
$page_title = 'Personalização';
require_once 'includes/header.php';

$auth->requireAdmin();

$config_class = new Config();
$error = '';
$success = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido.';
    } else {
        $data = [
            'fonte' => $_POST['fonte'] ?? 'Inter',
            'cor_primaria' => $_POST['cor_primaria'] ?? '#007bff',
            'cor_secundaria' => $_POST['cor_secundaria'] ?? '#6c757d',
            'cor_destaque' => $_POST['cor_destaque'] ?? '#28a745',
            'nome_empresa' => trim($_POST['nome_empresa'] ?? 'Sistema de Veículos')
        ];
        
        $logo = $_FILES['logo'] ?? null;
        
        if (empty($data['nome_empresa'])) {
            $error = 'Nome da empresa é obrigatório.';
        } else {
            if ($config_class->update($data, $logo)) {
                $success = 'Configurações atualizadas com sucesso!';
                // Recarregar configurações
                $config = $config_class->get();
            } else {
                $error = 'Erro ao atualizar configurações.';
            }
        }
    }
}

$current_config = $config_class->get();

// Fontes disponíveis do Google Fonts
$fonts = [
    'Inter' => 'Inter',
    'Roboto' => 'Roboto',
    'Open Sans' => 'Open+Sans',
    'Lato' => 'Lato',
    'Montserrat' => 'Montserrat',
    'Poppins' => 'Poppins',
    'Source Sans Pro' => 'Source+Sans+Pro',
    'Nunito' => 'Nunito',
    'Ubuntu' => 'Ubuntu',
    'Raleway' => 'Raleway'
];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Personalização do Sistema</h1>
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

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Configurações Visuais</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="mb-4">
                        <label for="nome_empresa" class="form-label">Nome da Empresa *</label>
                        <input type="text" class="form-control" name="nome_empresa" id="nome_empresa" required
                               value="<?= escape($current_config['nome_empresa']) ?>">
                        <div class="invalid-feedback">
                            Por favor, informe o nome da empresa.
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="logo" class="form-label">Logo da Empresa</label>
                        <input type="file" class="form-control" name="logo" id="logo" accept="image/*">
                        <div class="form-text">Formatos aceitos: JPG, PNG, GIF, SVG. Tamanho máximo: 2MB</div>
                        <?php if ($current_config['logo']): ?>
                            <div class="mt-2">
                                <small class="text-muted">Logo atual:</small><br>
                                <img src="<?= UPLOADS_URL ?>/logos/<?= escape($current_config['logo']) ?>" 
                                     alt="Logo atual" class="img-thumbnail" style="max-height: 60px;">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-4">
                        <label for="fonte" class="form-label">Fonte do Sistema</label>
                        <select class="form-select" name="fonte" id="fonte">
                            <?php foreach ($fonts as $name => $value): ?>
                                <option value="<?= $name ?>" <?= $current_config['fonte'] === $name ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <h6 class="mb-3">Cores do Sistema</h6>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="cor_primaria" class="form-label">Cor Primária</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="cor_primaria" id="cor_primaria"
                                           value="<?= escape($current_config['cor_primaria']) ?>">
                                    <input type="text" class="form-control" id="cor_primaria_text" 
                                           value="<?= escape($current_config['cor_primaria']) ?>" readonly>
                                </div>
                                <div class="form-text">Cor principal do sistema (botões, links, etc.)</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="cor_secundaria" class="form-label">Cor Secundária</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="cor_secundaria" id="cor_secundaria"
                                           value="<?= escape($current_config['cor_secundaria']) ?>">
                                    <input type="text" class="form-control" id="cor_secundaria_text" 
                                           value="<?= escape($current_config['cor_secundaria']) ?>" readonly>
                                </div>
                                <div class="form-text">Cor para elementos secundários</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="cor_destaque" class="form-label">Cor de Destaque</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="cor_destaque" id="cor_destaque"
                                           value="<?= escape($current_config['cor_destaque']) ?>">
                                    <input type="text" class="form-control" id="cor_destaque_text" 
                                           value="<?= escape($current_config['cor_destaque']) ?>" readonly>
                                </div>
                                <div class="form-text">Cor para elementos de sucesso e destaque</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" onclick="resetDefaults()">
                            <i class="bi bi-arrow-clockwise me-2"></i>Restaurar Padrões
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <span class="loading spinner-border spinner-border-sm me-2"></span>
                            <i class="bi bi-check-circle me-2"></i>
                            Salvar Configurações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Preview</h5>
            </div>
            <div class="card-body" id="preview">
                <div class="text-center mb-3">
                    <div id="preview-logo">
                        <?php if ($current_config['logo']): ?>
                            <img src="<?= UPLOADS_URL ?>/logos/<?= escape($current_config['logo']) ?>" 
                                 alt="Logo" class="img-fluid" style="max-height: 60px;">
                        <?php endif; ?>
                    </div>
                    <h5 id="preview-nome" class="mt-2"><?= escape($current_config['nome_empresa']) ?></h5>
                </div>
                
                <div class="mb-3">
                    <button type="button" class="btn btn-primary w-100" id="preview-btn-primary">
                        Botão Primário
                    </button>
                </div>
                
                <div class="mb-3">
                    <button type="button" class="btn btn-secondary w-100" id="preview-btn-secondary">
                        Botão Secundário
                    </button>
                </div>
                
                <div class="mb-3">
                    <div class="alert alert-success" id="preview-alert-success">
                        <i class="bi bi-check-circle me-2"></i>Mensagem de sucesso
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header" id="preview-card-header">
                        Card de Exemplo
                    </div>
                    <div class="card-body">
                        <p class="card-text">Este é um exemplo de como ficará o sistema com as cores selecionadas.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Dicas</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li><i class="bi bi-check-circle text-success me-2"></i>Use cores contrastantes</li>
                    <li><i class="bi bi-check-circle text-success me-2"></i>Teste em diferentes dispositivos</li>
                    <li><i class="bi bi-check-circle text-success me-2"></i>Mantenha a identidade visual</li>
                    <li><i class="bi bi-check-circle text-success me-2"></i>Logo em formato SVG é ideal</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Atualizar preview em tempo real
function updatePreview() {
    const nomeEmpresa = document.getElementById('nome_empresa').value;
    const corPrimaria = document.getElementById('cor_primaria').value;
    const corSecundaria = document.getElementById('cor_secundaria').value;
    const corDestaque = document.getElementById('cor_destaque').value;
    const fonte = document.getElementById('fonte').value;
    
    // Atualizar nome
    document.getElementById('preview-nome').textContent = nomeEmpresa;
    
    // Atualizar fonte
    document.getElementById('preview').style.fontFamily = `'${fonte}', sans-serif`;
    
    // Atualizar cores
    document.getElementById('preview-btn-primary').style.backgroundColor = corPrimaria;
    document.getElementById('preview-btn-primary').style.borderColor = corPrimaria;
    
    document.getElementById('preview-btn-secondary').style.backgroundColor = corSecundaria;
    document.getElementById('preview-btn-secondary').style.borderColor = corSecundaria;
    
    document.getElementById('preview-alert-success').style.backgroundColor = corDestaque + '20';
    document.getElementById('preview-alert-success').style.borderColor = corDestaque;
    document.getElementById('preview-alert-success').style.color = corDestaque;
    
    document.getElementById('preview-card-header').style.backgroundColor = corPrimaria;
    document.getElementById('preview-card-header').style.color = 'white';
}

// Event listeners para atualização em tempo real
document.getElementById('nome_empresa').addEventListener('input', updatePreview);
document.getElementById('fonte').addEventListener('change', updatePreview);

// Cores
['cor_primaria', 'cor_secundaria', 'cor_destaque'].forEach(function(colorId) {
    const colorInput = document.getElementById(colorId);
    const textInput = document.getElementById(colorId + '_text');
    
    colorInput.addEventListener('input', function() {
        textInput.value = this.value;
        updatePreview();
    });
});

// Preview de logo
document.getElementById('logo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewLogo = document.getElementById('preview-logo');
            previewLogo.innerHTML = `<img src="${e.target.result}" alt="Logo" class="img-fluid" style="max-height: 60px;">`;
        };
        reader.readAsDataURL(file);
    }
});

// Restaurar padrões
function resetDefaults() {
    if (confirm('Tem certeza que deseja restaurar as configurações padrão?')) {
        document.getElementById('nome_empresa').value = 'Sistema de Veículos';
        document.getElementById('fonte').value = 'Inter';
        document.getElementById('cor_primaria').value = '#007bff';
        document.getElementById('cor_secundaria').value = '#6c757d';
        document.getElementById('cor_destaque').value = '#28a745';
        
        // Atualizar campos de texto das cores
        document.getElementById('cor_primaria_text').value = '#007bff';
        document.getElementById('cor_secundaria_text').value = '#6c757d';
        document.getElementById('cor_destaque_text').value = '#28a745';
        
        updatePreview();
    }
}

// Inicializar preview
updatePreview();
</script>

<?php require_once 'includes/footer.php'; ?>