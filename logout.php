<?php
require_once 'config/config.php';
require_once 'config/database.php';

$auth = new Auth();
$auth->logout();
?>