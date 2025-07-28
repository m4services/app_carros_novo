<?php
// Arquivo para testar a conexão com o banco de dados
define('ROOT_PATH', __DIR__);

echo "<h1>Teste de Conexão - Sistema de Veículos</h1>";

// Verificar PHP
echo "<h2>✓ Informações do PHP</h2>";
echo "Versão do PHP: " . PHP_VERSION . "<br>";
echo "Extensões necessárias: ";
$required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
foreach ($required_extensions as $ext) {
    echo $ext . ": " . (extension_loaded($ext) ? "✓" : "✗") . " ";
}
echo "<br><br>";

// Verificar arquivos
echo "<h2>✓ Verificação de Arquivos</h2>";
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
echo "<br>";

// Verificar diretórios
echo "<h2>✓ Verificação de Diretórios</h2>";
$dirs_to_check = [
    'uploads',
    'uploads/usuarios',
    'uploads/veiculos',
    'uploads/logos',
    'logs'
];

foreach ($dirs_to_check as $dir) {
    $path = ROOT_PATH . '/' . $dir;
    $exists = is_dir($path);
    $writable = $exists ? is_writable($path) : false;
    echo $dir . ": " . ($exists ? "✓ Existe" : "✗ Não existe") . 
         ($exists ? ($writable ? " (Gravável)" : " (Não gravável)") : "") . "<br>";
}
echo "<br>";

// Testar conexão com banco
echo "<h2>✓ Teste de Conexão com Banco</h2>";
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
    echo "Password: " . (empty($password) ? "(vazio)" : "(definida)") . "<br><br>";
    
    $pdo = new PDO(
        "mysql:host=$host;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✓ Conexão com MySQL bem-sucedida<br>";
    
    // Verificar se o banco existe
    $stmt = $pdo->query("SHOW DATABASES LIKE '$database'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Banco de dados '$database' existe<br>";
        
        // Conectar ao banco específico
        $pdo = new PDO(
            "mysql:host=$host;dbname=$database;charset=utf8mb4",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Verificar tabelas
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "✓ Tabelas encontradas: " . implode(', ', $tables) . "<br>";
            
            // Verificar se há usuários
            if (in_array('usuarios', $tables)) {
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
                $count = $stmt->fetch()['total'];
                echo "✓ Total de usuários: $count<br>";
                
                if ($count > 0) {
                    $stmt = $pdo->query("SELECT nome, email, perfil FROM usuarios LIMIT 3");
                    $users = $stmt->fetchAll();
                    echo "✓ Usuários encontrados:<br>";
                    foreach ($users as $user) {
                        echo "&nbsp;&nbsp;- {$user['nome']} ({$user['email']}) - {$user['perfil']}<br>";
                    }
                }
            }
        } else {
            echo "⚠️ Nenhuma tabela encontrada. Execute o script SQL para criar as tabelas.<br>";
        }
    } else {
        echo "⚠️ Banco de dados '$database' não existe. Criando...<br>";
        $pdo->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ Banco de dados '$database' criado com sucesso<br>";
        echo "⚠️ Execute o script SQL para criar as tabelas.<br>";
    }
    
} catch (Exception $e) {
    echo "✗ Erro na conexão: " . $e->getMessage() . "<br>";
    echo "<br><strong>Possíveis soluções:</strong><br>";
    echo "1. Verifique se o MySQL está rodando<br>";
    echo "2. Verifique as credenciais no arquivo .env<br>";
    echo "3. Verifique se o usuário tem permissões adequadas<br>";
}

echo "<br><hr>";
echo "<h2>✓ Próximos Passos</h2>";
echo "1. Se o banco não existe, execute o script SQL em supabase/migrations/<br>";
echo "2. Acesse <a href='login.php'>login.php</a> para testar o sistema<br>";
echo "3. Use: admin@sistema.com / admin123 para fazer login<br>";
echo "4. <strong>REMOVA este arquivo em produção!</strong><br>";
?>