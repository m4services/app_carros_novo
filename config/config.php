<?php
// Configurações gerais do sistema
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir ROOT_PATH se não estiver definido
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

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

// Configurações de erro
$is_production = ($_ENV['APP_ENV'] ?? 'development') === 'production';
if ($is_production) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . '/logs/error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . '/logs/error.log');
}

// Criar diretórios necessários
$dirs = [
    ROOT_PATH . '/logs',
    ROOT_PATH . '/uploads',
    ROOT_PATH . '/uploads/usuarios',
    ROOT_PATH . '/uploads/veiculos',
    ROOT_PATH . '/uploads/logos'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

// URLs base
define('BASE_URL', $_ENV['APP_URL'] ?? 'http://localhost:8000');
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');

// Paths
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// Autoload de classes
spl_autoload_register(function($className) {
    $file = ROOT_PATH . '/classes/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    return false;
});

// Função para redirecionar
function redirect($url) {
    if (headers_sent()) {
        echo '<script>window.location.href = "' . BASE_URL . $url . '";</script>';
        echo '<meta http-equiv="refresh" content="0;url=' . BASE_URL . $url . '">';
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
    if (!$date || $date === '0000-00-00') return '';
    return date($format, strtotime($date));
}

// Função para formatar data e hora
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (!$datetime || $datetime === '0000-00-00 00:00:00') return '';
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

// Função para debug
function debug($data, $die = false) {
    if (!$is_production) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        if ($die) die();
    }
}
?>