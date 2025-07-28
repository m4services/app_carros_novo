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
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }
        
        * {
            font-family: var(--font-family);
        }
        
        body {
            background: var(--bg-primary);
            min-height: 100vh;
            color: var(--text-primary);
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
        
        /* Modern Layout Styles */
        .main-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            text-align: center;
        }
        
        .sidebar-logo {
            max-height: 40px;
            width: auto;
        }
        
        .sidebar-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }
        
        .sidebar .nav-link {
            color: var(--text-secondary);
            transition: all 0.3s ease;
            border-radius: 0.5rem;
            margin: 0.125rem 0.75rem;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            text-decoration: none;
            font-weight: 500;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: var(--primary-color);
            background-color: color-mix(in srgb, var(--primary-color) 10%, transparent);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
        }
        
        .main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
        
        .top-bar {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .top-bar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--text-primary);
            cursor: pointer;
        }
        
        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-menu {
            position: relative;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid var(--border-color);
            transition: border-color 0.2s;
        }
        
        .user-avatar:hover {
            border-color: var(--primary-color);
        }
        
        .user-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            font-size: 1.25rem;
        }
        
        .content-area {
            flex: 1;
            padding: 1.5rem;
        }
        
        /* Dashboard Styles */
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .welcome-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }
        
        .welcome-subtitle {
            color: var(--text-secondary);
            margin: 0.25rem 0 0 0;
        }
        
        .btn-quick-action {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .btn-quick-action:hover {
            background: color-mix(in srgb, var(--primary-color) 90%, black);
            transform: translateY(-1px);
        }
        
        .maintenance-alert {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 1px solid #f59e0b;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
        }
        
        .maintenance-alert.danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border-color: #ef4444;
        }
        
        .alert-icon {
            font-size: 1.5rem;
            color: #f59e0b;
        }
        
        .maintenance-alert.danger .alert-icon {
            color: #ef4444;
        }
        
        .alert-content h5 {
            margin: 0 0 0.5rem 0;
            font-weight: 600;
        }
        
        .maintenance-list {
            margin: 0;
            padding-left: 1rem;
        }
        
        .vehicles-section {
            margin-bottom: 2rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }
        
        .section-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }
        
        .btn-refresh {
            background: none;
            border: 1px solid var(--border-color);
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-refresh:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-add {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        
        .btn-add:hover {
            background: color-mix(in srgb, var(--primary-color) 90%, black);
            color: white;
        }
        
        .vehicles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .vehicle-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .vehicle-card.available:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        
        .vehicle-card.unavailable {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .vehicle-image {
            position: relative;
            height: 180px;
            overflow: hidden;
        }
        
        .vehicle-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .vehicle-placeholder {
            width: 100%;
            height: 100%;
            background: var(--bg-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--text-secondary);
        }
        
        .vehicle-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        
        .status-badge.available {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-badge.warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-badge.unavailable {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .vehicle-info {
            padding: 1.25rem;
        }
        
        .vehicle-info h3 {
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
        }
        
        .vehicle-details {
            display: flex;
            justify-content: space-between;
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .plate {
            background: var(--bg-primary);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 600;
        }
        
        .maintenance-warning {
            color: #f59e0b;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
        }
        
        .empty-icon {
            font-size: 4rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }
        
        .recent-trips-section {
            margin-bottom: 2rem;
        }
        
        .trips-list {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            overflow: hidden;
        }
        
        .trip-item {
            display: flex;
            align-items: center;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.2s;
        }
        
        .trip-item:last-child {
            border-bottom: none;
        }
        
        .trip-item:hover {
            background: var(--bg-primary);
        }
        
        .trip-icon {
            width: 40px;
            height: 40px;
            background: var(--bg-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: var(--primary-color);
        }
        
        .trip-info {
            flex: 1;
        }
        
        .trip-destination {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .trip-details {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .trip-km {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .btn-view-all {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
        }
        
        .btn-view-all:hover {
            text-decoration: underline;
        }
        
        /* Modal Styles */
        .vehicle-options {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .vehicle-option {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .vehicle-option:hover {
            border-color: var(--primary-color);
            background: color-mix(in srgb, var(--primary-color) 5%, transparent);
        }
        
        .vehicle-option-image {
            width: 60px;
            height: 60px;
            border-radius: 0.5rem;
            overflow: hidden;
            margin-right: 1rem;
            background: var(--bg-primary);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .vehicle-option-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .vehicle-option-image i {
            font-size: 1.5rem;
            color: var(--text-secondary);
        }
        
        .vehicle-option-info .name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .vehicle-option-info .details {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .selected-vehicle {
            background: var(--bg-primary);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--bg-secondary);
            border-top: 1px solid var(--border-color);
            padding: 0.75rem 0;
            z-index: 1000;
            display: none;
        }
        
        .bottom-nav-items {
            display: flex;
            justify-content: space-around;
            align-items: center;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 0.75rem;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
            min-width: 60px;
        }
        
        .bottom-nav-item.active,
        .bottom-nav-item:hover {
            color: var(--primary-color);
            background: color-mix(in srgb, var(--primary-color) 10%, transparent);
        }
        
        .bottom-nav-item i {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .vehicles-grid {
                grid-template-columns: 1fr;
            }
            
            .bottom-nav {
                display: block;
            }
            
            .content-area {
                padding-bottom: 5rem;
            }
        }
        
        /* Animations */
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .spin {
            animation: spin 1s linear infinite;
        }
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
    </style>
</head>
<body>
    <?php if (isset($auth) && $auth->isLoggedIn() && basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
    <div class="main-container">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <?php if ($config['logo']): ?>
                    <img src="<?= UPLOADS_URL ?>/logos/<?= escape($config['logo']) ?>" alt="Logo" class="sidebar-logo">
                <?php else: ?>
                    <h5 class="sidebar-title"><?= escape($config['nome_empresa']) ?></h5>
                <?php endif; ?>
            </div>
            
            <div class="sidebar-content">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                            <i class="bi bi-house"></i>Início
                            <span id="notification-badge" class="badge bg-danger ms-auto" style="display: none;">0</span>
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
                </ul>
            </div>
        </nav>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="top-bar-left">
                    <button class="menu-toggle" onclick="toggleSidebar()">
                        <i class="bi bi-list"></i>
                    </button>
                </div>
                
                <div class="top-bar-right">
                    <div class="user-menu dropdown">
                        <?php if ($config['logo']): ?>
                            <img src="<?= UPLOADS_URL ?>/logos/<?= escape($config['logo']) ?>" alt="Logo" class="sidebar-logo">
                        <?php else: ?>
                            <h5 class="sidebar-title"><?= escape($config['nome_empresa']) ?></h5>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['user_photo']) && $_SESSION['user_photo']): ?>
                            <img src="<?= UPLOADS_URL ?>/usuarios/<?= escape($_SESSION['user_photo']) ?>" 
                                 alt="<?= escape($_SESSION['user_name']) ?>" 
                                 class="user-avatar" 
                                 data-bs-toggle="dropdown">
                        <?php else: ?>
                            <div class="user-placeholder" data-bs-toggle="dropdown">
                                <i class="bi bi-person"></i>
                            </div>
                        <?php endif; ?>
                        
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <h6 class="dropdown-header"><?= escape($_SESSION['user_name'] ?? 'Usuário') ?></h6>
                            </li>
                            <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person me-2"></i>Perfil</a></li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="showNotifications()">
                                    <i class="bi bi-bell me-2"></i>Notificações
                                    <span id="user-notification-badge" class="badge bg-danger ms-2" style="display: none;">0</span>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="content-area">
    </div>
    
    <!-- Bottom Navigation (Mobile) -->
    <div class="bottom-nav">
        <div class="bottom-nav-items">
            <a href="dashboard.php" class="bottom-nav-item <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-house"></i>
                <span>Início</span>
            </a>
            <?php if (isset($auth) && $auth->isAdmin()): ?>
            <a href="veiculos.php" class="bottom-nav-item <?= in_array(basename($_SERVER['PHP_SELF']), ['veiculos.php', 'veiculo-form.php']) ? 'active' : '' ?>">
                <i class="bi bi-truck"></i>
                <span>Veículos</span>
            </a>
            <a href="manutencoes.php" class="bottom-nav-item <?= in_array(basename($_SERVER['PHP_SELF']), ['manutencoes.php', 'manutencao-form.php']) ? 'active' : '' ?>">
                <i class="bi bi-tools"></i>
                <span>Manutenções</span>
            </a>
            <?php endif; ?>
            <a href="relatorios.php" class="bottom-nav-item <?= basename($_SERVER['PHP_SELF']) === 'relatorios.php' ? 'active' : '' ?>">
                <i class="bi bi-graph-up"></i>
                <span>Relatórios</span>
            </a>
            <a href="perfil.php" class="bottom-nav-item <?= basename($_SERVER['PHP_SELF']) === 'perfil.php' ? 'active' : '' ?>">
                <i class="bi bi-person"></i>
                <span>Perfil</span>
            </a>
        </div>
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