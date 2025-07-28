<?php
// Definir ROOT_PATH se não estiver definido
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// Incluir configurações
try {
    require_once ROOT_PATH . '/config/config.php';
    require_once ROOT_PATH . '/config/database.php';
    
    // Carregar configurações de produção se existir
    $production_file = ROOT_PATH . '/config/production.php';
    if (file_exists($production_file)) {
        require_once $production_file;
    }
} catch (Exception $e) {
    error_log('Erro ao carregar configurações: ' . $e->getMessage());
    
    // Fallback básico
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    function escape($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
    
    $config = [
        'logo' => null,
        'fonte' => 'Inter',
        'cor_primaria' => '#007bff',
        'cor_secundaria' => '#6c757d',
        'cor_destaque' => '#28a745',
        'nome_empresa' => 'Sistema de Veículos'
    ];
}

// Inicializar classes principais
try {
    if (class_exists('Auth')) {
        $auth = new Auth();
    } else {
        throw new Exception('Classe Auth não encontrada');
    }
    
    if (class_exists('Config')) {
        $config_class = new Config();
        $config = $config_class->get();
    } else {
        throw new Exception('Classe Config não encontrada');
    }
} catch (Exception $e) {
    error_log('Erro ao inicializar classes: ' . $e->getMessage());
    
    // Configuração de fallback
    if (!isset($config)) {
        $config = [
            'logo' => null,
            'fonte' => 'Inter',
            'cor_primaria' => '#007bff',
            'cor_secundaria' => '#6c757d',
            'cor_destaque' => '#28a745',
            'nome_empresa' => 'Sistema de Veículos'
        ];
    }
    
    // Criar auth mock para evitar erros
    if (!isset($auth)) {
        $auth = new class {
            public function isLoggedIn() { return false; }
            public function isAdmin() { return false; }
            public function requireLogin() { 
                if (!headers_sent()) {
                    header('Location: login.php'); 
                }
                exit; 
            }
            public function checkRememberToken() {}
            public function checkTripRedirect() {}
        };
    }
}

// Verificar token de lembrar
try {
    if (isset($auth) && method_exists($auth, 'checkRememberToken')) {
        $auth->checkRememberToken();
    }
} catch (Exception $e) {
    error_log('Erro ao verificar token: ' . $e->getMessage());
}

// Verificar redirecionamento para deslocamento ativo
try {
    if (isset($auth) && method_exists($auth, 'checkTripRedirect')) {
        $auth->checkTripRedirect();
    }
} catch (Exception $e) {
    error_log('Erro ao verificar deslocamento ativo: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? escape($page_title) . ' - ' : '' ?><?= escape($config['nome_empresa']) ?></title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="<?= escape($config['cor_primaria']) ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?= escape($config['nome_empresa']) ?>">
    
    <!-- Manifest -->
    <link rel="manifest" href="manifest.json">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="apple-touch-icon" href="assets/icon-192x192.png">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=<?= urlencode($config['fonte']) ?>:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: <?= escape($config['cor_primaria']) ?>;
            --secondary-color: <?= escape($config['cor_secundaria']) ?>;
            --accent-color: <?= escape($config['cor_destaque']) ?>;
            --font-family: '<?= escape($config['fonte']) ?>', sans-serif;
        }
        
        * {
            font-family: var(--font-family);
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: color-mix(in srgb, var(--primary-color) 90%, black);
            border-color: color-mix(in srgb, var(--primary-color) 90%, black);
        }
        
        .text-primary {
            color: var(--primary-color) !important;
        }
        
        .bg-primary {
            background-color: var(--primary-color) !important;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(0,123,255,0.15);
        }
        
        .sidebar {
            min-height: 100vh;
            background: white;
            border-right: 1px solid #dee2e6;
        }
        
        .sidebar .nav-link {
            color: #6c757d;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin: 0.125rem 0.5rem;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: var(--primary-color);
            background-color: rgba(13, 110, 253, 0.1);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                width: 280px;
                height: 100vh;
                z-index: 1050;
                transition: left 0.3s ease;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                margin-left: 0 !important;
            }
        }
        
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
        }
        
        .navbar-brand img {
            max-height: 40px;
        }
        
        .loading {
            display: none;
        }
        
        .loading.show {
            display: inline-block;
        }
    </style>
</head>
<body>
    <?php if (isset($auth) && $auth->isLoggedIn() && basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="p-3 border-bottom">
                <?php if ($config['logo']): ?>
                    <img src="<?= UPLOADS_URL ?>/logos/<?= escape($config['logo']) ?>" alt="Logo" class="img-fluid">
                <?php else: ?>
                    <h5 class="mb-0"><?= escape($config['nome_empresa']) ?></h5>
                <?php endif; ?>
            </div>
            
            <div class="p-2">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                            <i class="bi bi-house"></i>Dashboard
                        </a>
                    </li>
                    
                    <?php if (isset($auth) && $auth->isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['veiculos.php', 'veiculo-form.php']) ? 'active' : '' ?>" href="veiculos.php">
                            <i class="bi bi-truck"></i>Veículos
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['usuarios.php', 'usuario-form.php']) ? 'active' : '' ?>" href="usuarios.php">
                            <i class="bi bi-people"></i>Usuários
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['manutencoes.php', 'manutencao-form.php']) ? 'active' : '' ?>" href="manutencoes.php">
                            <i class="bi bi-tools"></i>Manutenções
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'white-label.php' ? 'active' : '' ?>" href="white-label.php">
                            <i class="bi bi-palette"></i>Personalização
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'relatorios.php' ? 'active' : '' ?>" href="relatorios.php">
                            <i class="bi bi-graph-up"></i>Relatórios
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'perfil.php' ? 'active' : '' ?>" href="perfil.php">
                            <i class="bi bi-person"></i>Perfil
                        </a>
                    </li>
                    
                    <li class="nav-item mt-3">
                        <a class="nav-link text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i>Sair
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        
        <!-- Main Content -->
        <div class="main-content flex-fill">
            <!-- Top Bar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-outline-secondary d-lg-none me-2" type="button" onclick="toggleSidebar()">
                        <i class="bi bi-list"></i>
                    </button>
                    
                    <div class="navbar-nav ms-auto">
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                <?php if (isset($_SESSION['user_photo']) && $_SESSION['user_photo']): ?>
                                    <img src="<?= UPLOADS_URL ?>/usuarios/<?= escape($_SESSION['user_photo']) ?>" 
                                         alt="<?= escape($_SESSION['user_name']) ?>" 
                                         class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                <?php else: ?>
                                    <i class="bi bi-person-circle me-2" style="font-size: 1.5rem;"></i>
                                <?php endif; ?>
                                <?= escape($_SESSION['user_name'] ?? 'Usuário') ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person me-2"></i>Perfil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Content -->
            <div class="container-fluid p-4">
    <?php endif; ?>