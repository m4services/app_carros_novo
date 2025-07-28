<?php
// Configurações gerais do sistema
session_start();

// Carregar variáveis de ambiente se existir arquivo .env
if (file_exists(ROOT_PATH . '/.env')) {
    $lines = file(ROOT_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de erro (desabilitar em produção)
$is_production = ($_ENV['APP_ENV'] ?? 'production') === 'production';
if (!$is_production) {
    error_reporting(E_ERROR | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . '/logs/error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Criar diretório de logs se não existir
if (!is_dir(ROOT_PATH . '/logs')) {
    @mkdir(ROOT_PATH . '/logs', 0755, true);
}

// URLs base
define('BASE_URL', $_ENV['APP_URL'] ?? 'http://localhost:8000');
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');

// Paths
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// Criar diretórios se não existirem
if (!is_dir(UPLOADS_PATH)) {
    @mkdir(UPLOADS_PATH, 0755, true);
}
if (!is_dir(UPLOADS_PATH . '/usuarios')) {
    @mkdir(UPLOADS_PATH . '/usuarios', 0777, true);
}
if (!is_dir(UPLOADS_PATH . '/veiculos')) {
    @mkdir(UPLOADS_PATH . '/veiculos', 0777, true);
}
if (!is_dir(UPLOADS_PATH . '/logos')) {
    @mkdir(UPLOADS_PATH . '/logos', 0777, true);
}

// Função para incluir arquivos
function includeFile($path) {
    $fullPath = ROOT_PATH . '/' . $path;
    if (file_exists($fullPath)) {
        include_once $fullPath;
        return true;
    }
    return false;
}

// Autoload de classes
spl_autoload_register(function($className) {
    $paths = [
        'classes/',
    ];
    
    foreach ($paths as $path) {
        $file = ROOT_PATH . '/' . $path . $className . '.php';
        if (file_exists($file)) {
            include_once $file;
            return;
        }
    }
});

// Função para redirecionar
function redirect($url) {
    if (headers_sent()) {
        echo '<script>window.location.href = "' . BASE_URL . $url . '";</script>';
    } else {
        header("Location: " . BASE_URL . $url);
    }
    exit;
}

// Função para escapar HTML
function escape($string) {
    if ($string === null) return '';
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Função para formatar data
function formatDate($date, $format = 'd/m/Y') {
    if (!$date) return '';
    if ($date === '0000-00-00') return '';
    return date($format, strtotime($date));
}

// Função para formatar data e hora
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (!$datetime) return '';
    if ($datetime === '0000-00-00 00:00:00') return '';
    return date($format, strtotime($datetime));
}

// Função para gerar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Função para validar token CSRF
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Função para debug (remover em produção)
function debug($data, $die = false) {
    if (!$is_production) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        if ($die) die();
    }
}
?>