<?php
// Configuração do banco de dados
class Database {
    private static $instance = null;
    private $connection;
    
    private $host;
    private $database;
    private $username;
    private $password;
    
    private function __construct() {
        // Configurações do banco de dados
        $this->host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
        $this->database = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'sistema_veiculos');
        $this->username = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root');
        $this->password = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');
        
        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->database};charset=utf8mb4",
                $this->username,
                $this->password,
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
            
            // Em desenvolvimento, mostrar erro específico
            $is_production = ($_ENV['APP_ENV'] ?? 'development') === 'production';
            if (!$is_production) {
                die("Erro de conexão com banco: " . $e->getMessage() . "<br><br>Verifique se o MySQL está rodando e se as configurações estão corretas.");
            }
            
            http_response_code(500);
            die("Erro interno do servidor. Tente novamente em alguns minutos.");
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
    
    // Método para testar conexão
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