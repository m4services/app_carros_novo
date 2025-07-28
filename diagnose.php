<?php
// Diagnóstico completo do sistema
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', __DIR__);

echo "<!DOCTYPE html><html><head><title>Diagnóstico do Sistema</title>";
echo "<style>body{font-family:Arial;margin:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;}</style>";
echo "</head><body>";
echo "<h1>🔍 Diagnóstico Completo do Sistema</h1>";

// 1. Verificar PHP
echo "<h2>1. Verificação do PHP</h2>";
echo "Versão: " . PHP_VERSION . "<br>";
$required = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'session'];
foreach ($required as $ext) {
    $loaded = extension_loaded($ext);
    echo "<span class='" . ($loaded ? 'ok' : 'error') . "'>";
    echo $ext . ": " . ($loaded ? "✓" : "✗") . "</span><br>";
}

// 2. Verificar arquivos críticos
echo "<h2>2. Arquivos Críticos</h2>";
$critical_files = [
    'config/config.php',
    'config/database.php', 
    'classes/Auth.php',
    'classes/Config.php',
    'classes/Database.php',
    'includes/header.php',
    'includes/footer.php'
];

foreach ($critical_files as $file) {
    $exists = file_exists(ROOT_PATH . '/' . $file);
    $readable = $exists ? is_readable(ROOT_PATH . '/' . $file) : false;
    echo "<span class='" . ($exists && $readable ? 'ok' : 'error') . "'>";
    echo $file . ": " . ($exists ? ($readable ? "✓" : "Existe mas não é legível") : "✗ Não existe") . "</span><br>";
}

// 3. Verificar diretórios
echo "<h2>3. Diretórios e Permissões</h2>";
$dirs = ['uploads', 'uploads/usuarios', 'uploads/veiculos', 'uploads/logos', 'logs'];
foreach ($dirs as $dir) {
    $path = ROOT_PATH . '/' . $dir;
    $exists = is_dir($path);
    $writable = $exists ? is_writable($path) : false;
    
    if (!$exists) {
        @mkdir($path, 0777, true);
        $exists = is_dir($path);
        $writable = $exists ? is_writable($path) : false;
    }
    
    echo "<span class='" . ($exists && $writable ? 'ok' : 'error') . "'>";
    echo $dir . ": " . ($exists ? ($writable ? "✓" : "Existe mas não é gravável") : "✗") . "</span><br>";
}

// 4. Testar .env
echo "<h2>4. Configuração .env</h2>";
if (file_exists(ROOT_PATH . '/.env')) {
    echo "<span class='ok'>✓ Arquivo .env existe</span><br>";
    $env_content = file_get_contents(ROOT_PATH . '/.env');
    if (strpos($env_content, 'DB_HOST') !== false) {
        echo "<span class='ok'>✓ Configurações de banco encontradas</span><br>";
    } else {
        echo "<span class='error'>✗ Configurações de banco não encontradas</span><br>";
    }
} else {
    echo "<span class='error'>✗ Arquivo .env não existe</span><br>";
}

// 5. Testar conexão com banco
echo "<h2>5. Conexão com Banco de Dados</h2>";
try {
    // Carregar .env manualmente
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
    
    echo "Tentando conectar em: $host com usuário: $username<br>";
    
    $pdo = new PDO(
        "mysql:host=$host;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<span class='ok'>✓ Conexão MySQL OK</span><br>";
    
    // Verificar banco
    $stmt = $pdo->query("SHOW DATABASES LIKE '$database'");
    if ($stmt->rowCount() > 0) {
        echo "<span class='ok'>✓ Banco '$database' existe</span><br>";
        
        $pdo = new PDO(
            "mysql:host=$host;dbname=$database;charset=utf8mb4",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Verificar tabelas
        $required_tables = ['usuarios', 'veiculos', 'deslocamentos', 'manutencoes', 'configuracoes'];
        $stmt = $pdo->query("SHOW TABLES");
        $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($required_tables as $table) {
            $exists = in_array($table, $existing_tables);
            echo "<span class='" . ($exists ? 'ok' : 'error') . "'>";
            echo "Tabela $table: " . ($exists ? "✓" : "✗") . "</span><br>";
        }
        
        // Verificar usuário admin
        if (in_array('usuarios', $existing_tables)) {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE email = 'admin@sistema.com'");
            $admin_exists = $stmt->fetch()['total'] > 0;
            echo "<span class='" . ($admin_exists ? 'ok' : 'warning') . "'>";
            echo "Usuário admin: " . ($admin_exists ? "✓" : "⚠️ Não existe") . "</span><br>";
        }
        
    } else {
        echo "<span class='error'>✗ Banco '$database' não existe</span><br>";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>✗ Erro: " . $e->getMessage() . "</span><br>";
}

// 6. Testar carregamento das classes
echo "<h2>6. Teste de Classes</h2>";
try {
    require_once ROOT_PATH . '/config/config.php';
    echo "<span class='ok'>✓ config.php carregado</span><br>";
    
    require_once ROOT_PATH . '/config/database.php';
    echo "<span class='ok'>✓ database.php carregado</span><br>";
    
    $db = Database::getInstance();
    echo "<span class='ok'>✓ Classe Database OK</span><br>";
    
    $auth = new Auth();
    echo "<span class='ok'>✓ Classe Auth OK</span><br>";
    
    $config_class = new Config();
    echo "<span class='ok'>✓ Classe Config OK</span><br>";
    
} catch (Exception $e) {
    echo "<span class='error'>✗ Erro ao carregar classes: " . $e->getMessage() . "</span><br>";
}

echo "<h2>7. Recomendações</h2>";
echo "<p>Se houver erros acima, corrija-os antes de prosseguir.</p>";
echo "<p><a href='login.php'>Testar Login</a> | <a href='dashboard.php'>Testar Dashboard</a></p>";
echo "</body></html>";
?>