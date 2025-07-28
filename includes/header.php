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
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
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
        
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175);
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary-color), color-mix(in srgb, var(--primary-color) 85%, black));
            min-height: 100vh;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            transition: all 0.3s ease;
            border-radius: 0.5rem;
            margin: 0.25rem 0;
            padding: 0.875rem 1.25rem;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.15);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(0,123,255,0.15);
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .shadow-lg {
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175) !important;
        }
        
        .input-group-lg .form-control {
            font-size: 1.1rem;
            padding: 0.75rem 1rem;
        }
        
        .gap-3 {
            gap: 1rem !important;
        }
        
        @media (max-width: 768px) {
            .container-fluid {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            
            .card-body {
                padding: 1.5rem !important;
            }
            
            .btn-lg {
                padding: 0.75rem 1.5rem;
                font-size: 1rem;
            }
            
            .input-group-lg .form-control {
                font-size: 1rem;
                padding: 0.5rem 0.75rem;
            }
        }
        
        .loading {
            display: none !important;
        }
        
        .loading.show {
            display: inline-block !important;
        }
        
        /* Loading overlay */
        .page-loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .page-loading.hide {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Loading overlay -->
    <div class="page-loading" id="pageLoading">
        <div class="text-center">
            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
            <div class="fw-bold">Carregando...</div>
        </div>
    </div>
    
    <?php if (isset($auth) && $auth->isLoggedIn() && basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse" id="sidebar">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <?php if ($config['logo']): ?>
                            <img src="<?= UPLOADS_URL ?>/logos/<?= escape($config['logo']) ?>" alt="Logo" class="img-fluid" style="max-height: 60px;">
                        <?php else: ?>
                            <h5 class="text-white mb-0"><?= escape($config['nome_empresa']) ?></h5>
                        <?php endif; ?>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                                <span id="notification-badge" class="badge bg-danger ms-2" style="display: none;">0</span>
                            </a>
                        </li>
                        
                        <?php if (isset($auth) && $auth->isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['veiculos.php', 'veiculo-form.php']) ? 'active' : '' ?>" href="veiculos.php">
                                <i class="bi bi-truck me-2"></i>Veículos
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['usuarios.php', 'usuario-form.php']) ? 'active' : '' ?>" href="usuarios.php">
                                <i class="bi bi-people me-2"></i>Usuários
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['manutencoes.php', 'manutencao-form.php']) ? 'active' : '' ?>" href="manutencoes.php">
                                <i class="bi bi-tools me-2"></i>Manutenções
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'white-label.php' ? 'active' : '' ?>" href="white-label.php">
                                <i class="bi bi-palette me-2"></i>Personalização
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'relatorios.php' ? 'active' : '' ?>" href="relatorios.php">
                                <i class="bi bi-graph-up me-2"></i>Relatórios
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'perfil.php' ? 'active' : '' ?>" href="perfil.php">
                                <i class="bi bi-person me-2"></i>Meu Perfil
                            </a>
                        </li>
                    </ul>
                    
                    <hr class="text-white">
                    
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                            <?php if (isset($_SESSION['user_photo']) && $_SESSION['user_photo']): ?>
                                <img src="<?= UPLOADS_URL ?>/usuarios/<?= escape($_SESSION['user_photo']) ?>" alt="" width="32" height="32" class="rounded-circle me-2">
                            <?php else: ?>
                                <i class="bi bi-person-circle me-2" style="font-size: 2rem;"></i>
                            <?php endif; ?>
                            <strong><?= escape($_SESSION['user_name'] ?? 'Usuário') ?></strong>
                            <span id="user-notification-badge" class="badge bg-danger ms-2" style="display: none;">0</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                            <li>
                                <a class="dropdown-item" href="#" onclick="showNotifications()">
                                    <i class="bi bi-bell me-2"></i>Notificações
                                    <span id="dropdown-notification-badge" class="badge bg-danger ms-2" style="display: none;">0</span>
                                </a>
                            </li>
                            <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person me-2"></i>Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                        </ul>
                    </div>
                </div>
            </nav>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 animate-fade-in-up">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <button class="btn btn-outline-primary d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar">
                        <i class="bi bi-list"></i>
                    </button>
                </div>
                
                <!-- Modal de Notificações -->
                <div class="modal fade" id="notificationsModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="bi bi-bell me-2"></i>Notificações
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" id="notifications-content">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Carregando...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" onclick="markAllNotificationsRead()">
                                    <i class="bi bi-check-all me-2"></i>Marcar Todas como Lidas
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            </div>
                        </div>
                    </div>
                </div>
    <?php endif; ?>