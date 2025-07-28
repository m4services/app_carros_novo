<?php
// Arquivo temporário para debug - REMOVER EM PRODUÇÃO
define('ROOT_PATH', __DIR__);

echo "<h1>Debug do Sistema</h1>";

// Verificar PHP
echo "<h2>Informações do PHP</h2>";
echo "Versão do PHP: " . PHP_VERSION . "<br>";
echo "Extensões carregadas: " . implode(', ', get_loaded_extensions()) . "<br>";

// Verificar arquivos
echo "<h2>Verificação de Arquivos</h2>";
$files_to_check = [
    'config/config.php',
    'config/database.php',
    'classes/Auth.php',
    'classes/Config.php',
    'includes/header.php'
];

foreach ($files_to_check as $file) {
    $path = ROOT_PATH . '/' . $file;
    echo $file . ": " . (file_exists($path) ? "✓ Existe" : "✗ Não existe") . "<br>";
}

// Verificar diretórios
echo "<h2>Verificação de Diretórios</h2>";
$dirs_to_check = [
    'uploads',
    'uploads/usuarios',
    'uploads/veiculos',
    'uploads/logos',
    'logs'
];

foreach ($dirs_to_check as $dir) {
    $path = ROOT_PATH . '/' . $dir;
    echo $dir . ": " . (is_dir($path) ? "✓ Existe" : "✗ Não existe") . "<br>";
}

// Verificar permissões
echo "<h2>Verificação de Permissões</h2>";
echo "Permissão do diretório raiz: " . substr(sprintf('%o', fileperms(ROOT_PATH)), -4) . "<br>";

// Testar conexão com banco
echo "<h2>Teste de Conexão com Banco</h2>";
try {
    // Carregar variáveis de ambiente
    if (file_exists(ROOT_PATH . '/.env')) {
        $lines = file(ROOT_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
    }
    
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $database = $_ENV['DB_NAME'] ?? 'sistema_veiculos';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASS'] ?? '';
    
    echo "Host: $host<br>";
    echo "Database: $database<br>";
    echo "Username: $username<br>";
    
    $pdo = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✓ Conexão com banco de dados bem-sucedida<br>";
    
    // Verificar tabelas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tabelas encontradas: " . implode(', ', $tables) . "<br>";
    
} catch (Exception $e) {
    echo "✗ Erro na conexão: " . $e->getMessage() . "<br>";
}

// Verificar logs de erro
echo "<h2>Logs de Erro</h2>";
$error_log = ROOT_PATH . '/logs/error.log';
if (file_exists($error_log)) {
    $errors = file_get_contents($error_log);
    echo "<pre>" . htmlspecialchars($errors) . "</pre>";
} else {
    echo "Nenhum log de erro encontrado.<br>";
}

echo "<br><strong>Debug concluído. REMOVA este arquivo em produção!</strong>";
?>