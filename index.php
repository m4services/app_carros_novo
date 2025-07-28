<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

try {
    require_once 'config/config.php';
    require_once 'config/database.php';

    $auth = new Auth();
    $auth->checkRememberToken();

    if ($auth->isLoggedIn()) {
        redirect('/dashboard.php');
    } else {
        redirect('/login.php');
    }
} catch (Exception $e) {
    error_log('Erro no index.php: ' . $e->getMessage());
    header('Location: /login.php');
    exit;
}
?>