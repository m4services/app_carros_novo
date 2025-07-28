<?php
$page_title = 'Login';
require_once 'includes/header.php';

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
            if ($auth->login($email, $senha, $lembrar)) {
                redirect('/dashboard.php');
            } else {
                $error = 'Email ou senha incorretos.';
            }
        }
    }
}

// Se já está logado, redirecionar
if ($auth->isLoggedIn()) {
    redirect('/dashboard.php');
}
?>

<div class="container-fluid vh-100">
    <div class="row h-100">
        <div class="col-md-6 d-flex align-items-center justify-content-center bg-light">
            <div class="text-center">
                <?php if ($config['logo']): ?>
                    <img src="<?= UPLOADS_URL ?>/logos/<?= escape($config['logo']) ?>" alt="Logo" class="img-fluid mb-4" style="max-height: 120px;">
                <?php endif; ?>
                <h1 class="display-4 text-primary mb-3"><?= escape($config['nome_empresa']) ?></h1>
                <p class="lead text-muted">Sistema completo para controle de veículos, manutenções e deslocamentos.</p>
                <div class="mt-4">
                    <i class="bi bi-truck text-primary me-3" style="font-size: 2rem;"></i>
                    <i class="bi bi-tools text-primary me-3" style="font-size: 2rem;"></i>
                    <i class="bi bi-graph-up text-primary" style="font-size: 2rem;"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 d-flex align-items-center justify-content-center">
            <div class="w-100" style="max-width: 400px;">
                <div class="card">
                    <div class="card-body p-5">
                        <h3 class="card-title text-center mb-4">Entrar no Sistema</h3>
                        
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
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required 
                                           value="<?= escape($_POST['email'] ?? '') ?>" placeholder="seu@email.com">
                                    <div class="invalid-feedback">
                                        Por favor, insira um email válido.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="senha" name="senha" required 
                                           placeholder="Sua senha">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
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
                                    <label class="form-check-label" for="lembrar">
                                        Manter conectado por 30 dias
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <span class="loading spinner-border spinner-border-sm me-2"></span>
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                Entrar
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                Sistema desenvolvido com segurança e tecnologia PWA
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
    
    // Auto-focus no primeiro campo
    document.getElementById('email').focus();
</script>

<?php require_once 'includes/footer.php'; ?>