<?php
// Redirecionar para dashboard se logado, senão para login
require_once 'config/config.php';
require_once 'config/database.php';

$auth = new Auth();
$auth->checkRememberToken();

if ($auth->isLoggedIn()) {
    redirect('/dashboard.php');
} else {
    redirect('/login.php');
}
?>