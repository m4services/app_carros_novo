<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

$page_title = 'Login';

try {
    require_once 'includes/header.php';
} catch (Exception $e) {
    error_log('Erro ao carregar sistema: ' . $e->getMessage());
    die('Erro ao carregar sistema. Verifique as configurações.');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $lembrar = isset($_POST['lembrar']);
        
        if (empty($email) || empty($senha)) {
            $error = 'Por favor, preencha todos os campos.';
        } else {
            try {
                if (isset($auth) && $auth->login($email, $senha, $lembrar)) {
                    redirect('/dashboard.php');
                } else {
                    $error = 'Email ou senha incorretos.';
                }
            } catch (Exception $e) {
                error_log('Erro no login: ' . $e->getMessage());
                $error = 'Erro interno. Tente novamente.';
            }
        }
    }
}

// Se já está logado, redirecionar
try {
    if (isset($auth) && $auth->isLoggedIn()) {
        redirect('/dashboard.php');
    }
} catch (Exception $e) {
    error_log('Erro ao verificar login: ' . $e->getMessage());
}
?>

<div class="container-fluid vh-100">
    <div class="row h-100">
        <div class="col-md-6 d-flex align-items-center justify-content-center position-relative overflow-hidden">
            <div class="position-absolute top-0 start-0 w-100 h-100" 
                 style="background: linear-gradient(135deg, var(--primary-color) 0%, color-mix(in srgb, var(--primary-color) 85%, black) 100%); opacity: 0.95;"></div>
            <div class="text-center position-relative animate-fade-in-up">
                <?php if ($config['logo']): ?>
                    <img src="<?= UPLOADS_URL ?>/logos/<?= escape($config['logo']) ?>" alt="Logo" 
                         class="img-fluid mb-4" style="max-height: 140px; filter: brightness(0) invert(1);">
                <?php endif; ?>
                <h1 class="display-3 text-white mb-4 fw-bold">
                    <?= escape($config['nome_empresa']) ?>
                </h1>
                <p class="lead text-white mb-5" style="opacity: 0.9;">
                    Sistema completo para controle de veículos, manutenções e deslocamentos.
                </p>
            </div>
        </div>
        
        <div class="col-md-6 d-flex align-items-center justify-content-center bg-light">
            <div class="w-100" style="max-width: 400px;">
                <div class="card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-inline-flex mb-3">
                                <i class="bi bi-shield-lock text-primary" style="font-size: 2rem;"></i>
                            </div>
                            <h3 class="card-title fw-bold">Entrar no Sistema</h3>
                            <p class="text-muted">Acesse sua conta para continuar</p>
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
                        
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            
                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" required 
                                           value="<?= escape($_POST['email'] ?? '') ?>" placeholder="seu@email.com">
                                    <div class="invalid-feedback">
                                        Por favor, insira um email válido.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="senha" class="form-label fw-semibold">Senha</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="senha" name="senha" required 
                                           placeholder="Sua senha">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()" 
                                            style="border-left: none;">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                    <div class="invalid-feedback">
                                        Por favor, insira sua senha.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="lembrar" name="lembrar">
                                    <label class="form-check-label fw-semibold" for="lembrar">
                                        Manter conectado por 30 dias
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold">
                                <span class="loading spinner-border spinner-border-sm me-2"></span>
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                Entrar
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                <strong>Usuário padrão:</strong> admin@sistema.com<br>
                                <strong>Senha:</strong> admin123
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword() {
        const senhaInput = document.getElementById('senha');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (senhaInput.type === 'password') {
            senhaInput.type = 'text';
            toggleIcon.className = 'bi bi-eye-slash';
        } else {
            senhaInput.type = 'password';
            toggleIcon.className = 'bi bi-eye';
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const emailField = document.getElementById('email');
        if (emailField) {
            emailField.focus();
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>