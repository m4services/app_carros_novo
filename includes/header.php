<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Verificar se as classes existem antes de instanciar
if (!class_exists('Auth')) {
    die('Erro: Classe Auth não encontrada. Verifique se o arquivo classes/Auth.php existe.');
}

if (!class_exists('Config')) {
    die('Erro: Classe Config não encontrada. Verifique se o arquivo classes/Config.php existe.');
}

$auth = new Auth();
$config_class = new Config();
$config = $config_class->get();

// Verificar token de lembrar
try {
    $auth->checkRememberToken();
} catch (Exception $e) {
    error_log('Erro ao verificar token: ' . $e->getMessage());
}

// Verificar redirecionamento para deslocamento ativo
try {
    $auth->checkTripRedirect();
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
            --shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 1rem 3rem rgba(0, 0, 0, 0.175);
            --border-radius: 0.75rem;
            --border-radius-lg: 1rem;
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
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
        }
        
        .btn-primary:hover {
            background-color: color-mix(in srgb, var(--primary-color) 90%, black);
            border-color: color-mix(in srgb, var(--primary-color) 90%, black);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .btn-success {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
        }
        
        .btn-success:hover {
            background-color: color-mix(in srgb, var(--accent-color) 90%, black);
            border-color: color-mix(in srgb, var(--accent-color) 90%, black);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .btn {
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            box-shadow: var(--shadow-sm);
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }
        
        .text-primary {
            color: var(--primary-color) !important;
        }
        
        .bg-primary {
            background-color: var(--primary-color) !important;
        }
        
        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
        }
        
        .card {
            border: none;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), color-mix(in srgb, var(--primary-color) 90%, black));
            color: white;
            border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0 !important;
            padding: 1.5rem;
            border: none;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .vehicle-card {
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .vehicle-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1;
        }
        
        .vehicle-card:hover::before {
            opacity: 1;
        }
        
        .vehicle-card.unavailable {
            opacity: 0.6;
            cursor: not-allowed;
            filter: grayscale(50%);
        }
        
        .vehicle-card.unavailable img {
            filter: grayscale(100%) opacity(0.5);
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary-color), color-mix(in srgb, var(--primary-color) 85%, black));
            min-height: 100vh;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-lg);
            position: relative;
        }
        
        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.05"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.05"/><circle cx="50" cy="10" r="1" fill="white" opacity="0.03"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
            pointer-events: none;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: var(--border-radius);
            margin: 0.25rem 0;
            padding: 0.875rem 1.25rem;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        
        .sidebar .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.15);
            transform: translateX(5px);
            box-shadow: var(--shadow-sm);
        }
        
        .sidebar .nav-link:hover::before {
            left: 100%;
        }
        
        .main-content {
            background: transparent;
            min-height: 100vh;
            padding: 2rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(0,123,255,0.15);
            transform: translateY(-1px);
        }
        
        .form-control {
            border-radius: var(--border-radius);
            border: 2px solid #e9ecef;
            padding: 0.875rem 1.25rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
        
        .modal-content {
            border: none;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), color-mix(in srgb, var(--primary-color) 90%, black));
            color: white;
            border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0;
            padding: 1.5rem;
            border: none;
        }
        
        .table {
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        
        .table thead th {
            background: linear-gradient(135deg, var(--primary-color), color-mix(in srgb, var(--primary-color) 95%, black));
            color: white;
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(0,123,255,0.05);
            transform: scale(1.01);
        }
        
        .badge {
            border-radius: var(--border-radius);
            font-weight: 600;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .alert {
            border: none;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            backdrop-filter: blur(10px);
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.05));
            border-left: 4px solid var(--accent-color);
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05));
            border-left: 4px solid #dc3545;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 193, 7, 0.05));
            border-left: 4px solid #ffc107;
        }
        
        .alert-info {
            background: linear-gradient(135deg, rgba(13, 202, 240, 0.1), rgba(13, 202, 240, 0.05));
            border-left: 4px solid #0dcaf0;
        }
        
        /* Animações personalizadas */
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
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .animate-slide-in-left {
            animation: slideInLeft 0.6s ease-out;
        }
        
        /* Melhorias responsivas */
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
                position: fixed;
                z-index: 1050;
                width: 250px;
            }
            
            .sidebar.show {
                margin-left: 0;
            }
            
            .main-content {
                padding: 1rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .btn {
                padding: 0.625rem 1.25rem;
            }
        }
        
        /* Estados de loading melhorados */
        .loading {
            display: none;
        }
        
        .loading.show {
            display: inline-block;
        }
        
        /* Melhorias nos cards de estatísticas */
        .stats-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(255,255,255,0.7));
            backdrop-filter: blur(10px);
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .stats-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: var(--shadow-lg);
        }
        
        .stats-card .stats-icon {
            background: linear-gradient(135deg, var(--primary-color), color-mix(in srgb, var(--primary-color) 90%, black));
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: var(--shadow);
        }
        
        /* Melhorias no dropdown do usuário */
        .user-dropdown {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .user-dropdown:hover {
            background: rgba(255,255,255,0.2);
        }
        
        /* Scrollbar personalizada */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: color-mix(in srgb, var(--primary-color) 85%, black);
        }
        
        /* Melhorias nos inputs */
        .input-group-text {
            background: linear-gradient(135deg, var(--primary-color), color-mix(in srgb, var(--primary-color) 95%, black));
            color: white;
            border: none;
            border-radius: var(--border-radius) 0 0 var(--border-radius);
        }
        
        /* Efeitos de hover nos links */
        a {
            transition: all 0.3s ease;
        }
        
        a:hover {
            transform: translateY(-1px);
        }
        
        /* Melhorias na tipografia */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .display-1, .display-2, .display-3, .display-4 {
            font-weight: 700;
        }
        
        /* Estados de foco melhorados */
        .btn:focus,
        .form-control:focus,
        .form-select:focus {
            outline: none;
            box-shadow: 0 0 0 0.25rem rgba(0,123,255,0.15);
        }
        
        /* Melhorias nos modais */
        .modal-backdrop {
            backdrop-filter: blur(5px);
        }
        
        /* Transições suaves para todos os elementos */
        * {
            transition: color 0.3s ease, background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        /* Melhorias nos botões de ação */
        .btn-group .btn {
            margin: 0 2px;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.125rem;
        }
        
        /* Melhorias na navegação breadcrumb */
        .breadcrumb {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 1rem 1.5rem;
        }
        
        /* Melhorias nos tooltips */
        .tooltip {
            font-size: 0.875rem;
        }
        
        .tooltip-inner {
            background: rgba(0,0,0,0.9);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
        }
        
        /* Melhorias nos popovers */
        .popover {
            border: none;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(20px);
        }
        
        /* Melhorias nos progress bars */
        .progress {
            border-radius: var(--border-radius);
            background: rgba(0,0,0,0.1);
        }
        
        .progress-bar {
            background: linear-gradient(90deg, var(--primary-color), color-mix(in srgb, var(--primary-color) 90%, black));
        }
        
        /* Melhorias nos spinners */
        .spinner-border {
            color: var(--primary-color);
        }
        
        /* Melhorias nos close buttons */
        .btn-close {
            filter: invert(1);
            opacity: 0.8;
        }
        
        .btn-close:hover {
            opacity: 1;
            transform: scale(1.1);
        }
        
        /* Melhorias nos list groups */
        .list-group-item {
            border: none;
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(10px);
            margin-bottom: 0.5rem;
            border-radius: var(--border-radius) !important;
            transition: all 0.3s ease;
        }
        
        .list-group-item:hover {
            background: rgba(255,255,255,0.9);
            transform: translateX(5px);
        }
        
        /* Melhorias nos navs */
        .nav-tabs .nav-link {
            border: none;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            background: rgba(255,255,255,0.7);
            margin-right: 0.25rem;
        }
        
        .nav-tabs .nav-link.active {
            background: white;
            box-shadow: var(--shadow-sm);
        }
        
        /* Melhorias nos accordions */
        .accordion-item {
            border: none;
            margin-bottom: 0.5rem;
            border-radius: var(--border-radius-lg) !important;
            box-shadow: var(--shadow-sm);
        }
        
        .accordion-header button {
            border-radius: var(--border-radius-lg) !important;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
        }
        
        /* Melhorias nos carousels */
        .carousel-item img {
            border-radius: var(--border-radius-lg);
        }
        
        .carousel-control-prev,
        .carousel-control-next {
            background: linear-gradient(135deg, rgba(0,0,0,0.3), rgba(0,0,0,0.1));
            backdrop-filter: blur(10px);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        /* Melhorias nos offcanvas */
        .offcanvas {
            backdrop-filter: blur(20px);
            background: rgba(255,255,255,0.95);
        }
        
        /* Melhorias nos toasts */
        .toast {
            border: none;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(20px);
        }
        
        /* Melhorias nos dropdowns */
        .dropdown-menu {
            border: none;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(20px);
            background: rgba(255,255,255,0.95);
        }
        
        .dropdown-item {
            border-radius: var(--border-radius);
            margin: 0.125rem 0.5rem;
            transition: all 0.3s ease;
        }
        
        .dropdown-item:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(5px);
            }
            
            .sidebar.show {
                margin-left: 0;
            }
        }
        
        /* Melhorias nos pagination */
        .page-link {
            border: none;
            border-radius: var(--border-radius);
            margin: 0 0.125rem;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .page-link:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--primary-color);
            box-shadow: var(--shadow-sm);
        }
    </style>
</head>
<body>
    <?php if ($auth->isLoggedIn() && basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
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
                                <i class="bi bi-speedometer2 me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        
                        <?php if ($auth->isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['veiculos.php', 'veiculo-form.php']) ? 'active' : '' ?>" href="veiculos.php">
                                <i class="bi bi-truck me-2"></i>
                                Veículos
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['usuarios.php', 'usuario-form.php']) ? 'active' : '' ?>" href="usuarios.php">
                                <i class="bi bi-people me-2"></i>
                                Usuários
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['manutencoes.php', 'manutencao-form.php']) ? 'active' : '' ?>" href="manutencoes.php">
                                <i class="bi bi-tools me-2"></i>
                                Manutenções
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'white-label.php' ? 'active' : '' ?>" href="white-label.php">
                                <i class="bi bi-palette me-2"></i>
                                Personalização
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'relatorios.php' ? 'active' : '' ?>" href="relatorios.php">
                                <i class="bi bi-graph-up me-2"></i>
                                Relatórios
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'perfil.php' ? 'active' : '' ?>" href="perfil.php">
                                <i class="bi bi-person me-2"></i>
                                Meu Perfil
                            </a>
                        </li>
                    </ul>
                    
                    <hr class="text-white">
                    
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle user-dropdown" data-bs-toggle="dropdown">
                            <?php if ($_SESSION['user_photo']): ?>
                                <img src="<?= UPLOADS_URL ?>/usuarios/<?= escape($_SESSION['user_photo']) ?>" alt="" width="32" height="32" class="rounded-circle me-2">
                            <?php else: ?>
                                <i class="bi bi-person-circle me-2" style="font-size: 2rem;"></i>
                            <?php endif; ?>
                            <strong><?= escape($_SESSION['user_name']) ?></strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                            <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person me-2"></i>Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                        </ul>
                    </div>
                </div>
            </nav>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content animate-fade-in-up">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
                    <button class="btn btn-outline-primary d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar">
                        <i class="bi bi-list"></i>
                    </button>
                </div>
    <?php endif; ?>