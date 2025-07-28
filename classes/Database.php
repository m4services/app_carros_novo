<?php
// Configuração do banco de dados
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $database = $_ENV['DB_NAME'] ?? 'sistema_veiculos';
            $username = $_ENV['DB_USER'] ?? 'root';
            $password = $_ENV['DB_PASS'] ?? '';
            
            $this->connection = new PDO(
                "mysql:host={$host};dbname={$database};charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch (PDOException $e) {
            $error_msg = "Erro de conexão com banco de dados: " . $e->getMessage();
            error_log($error_msg);
            
            $is_production = ($_ENV['APP_ENV'] ?? 'development') === 'production';
            if (!$is_production) {
                die("
                <div style='padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px; font-family: Arial, sans-serif;'>
                    <h3>❌ Erro de Conexão com Banco de Dados</h3>
                    <p><strong>Erro:</strong> {$e->getMessage()}</p>
                    <p><strong>Host:</strong> {$host}</p>
                    <p><strong>Database:</strong> {$database}</p>
                    <p><strong>Username:</strong> {$username}</p>
                    <hr>
                    <p><strong>Soluções:</strong></p>
                    <ul>
                        <li>Verifique se o MySQL está rodando</li>
                        <li>Verifique as configurações no arquivo .env</li>
                        <li>Execute o script SQL para criar as tabelas</li>
                        <li>Verifique se o banco de dados existe</li>
                    </ul>
                </div>
                ");
            }
            
            http_response_code(500);
            die("Erro interno do servidor. Verifique as configurações do banco de dados.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function testConnection() {
        try {
            $stmt = $this->connection->query("SELECT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>