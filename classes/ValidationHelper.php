<?php
class ValidationHelper {
    
    /**
     * Valida CPF
     */
    public static function validateCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Valida email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Valida placa de veículo (formato brasileiro)
     */
    public static function validatePlate($plate) {
        $plate = strtoupper(preg_replace('/[^A-Z0-9]/', '', $plate));
        
        // Formato antigo: ABC1234
        if (preg_match('/^[A-Z]{3}[0-9]{4}$/', $plate)) {
            return true;
        }
        
        // Formato Mercosul: ABC1D23
        if (preg_match('/^[A-Z]{3}[0-9][A-Z][0-9]{2}$/', $plate)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Valida data
     */
    public static function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Valida senha forte
     */
    public static function validateStrongPassword($password) {
        if (strlen($password) < 8) {
            return false;
        }
        
        // Pelo menos uma letra minúscula, uma maiúscula, um número
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitiza string
     */
    public static function sanitizeString($string) {
        return trim(htmlspecialchars($string, ENT_QUOTES, 'UTF-8'));
    }
    
    /**
     * Valida upload de imagem
     */
    public static function validateImageUpload($file, $max_size = 5242880) { // 5MB default
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Parâmetros de upload inválidos');
        }
        
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('Nenhum arquivo foi enviado');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('Arquivo muito grande');
            default:
                throw new Exception('Erro desconhecido no upload');
        }
        
        if ($file['size'] > $max_size) {
            throw new Exception('Arquivo excede o tamanho máximo permitido');
        }
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file['tmp_name']);
        
        $allowed_types = [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif',
            'image/webp'
        ];
        
        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception('Tipo de arquivo não permitido');
        }
        
        return true;
    }
    
    /**
     * Gera nome único para arquivo
     */
    public static function generateUniqueFileName($original_name) {
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
        return uniqid('img_', true) . '.' . strtolower($extension);
    }
    
    /**
     * Valida KM (deve ser positivo e razoável)
     */
    public static function validateKilometers($km) {
        return is_numeric($km) && $km >= 0 && $km <= 9999999;
    }
    
    /**
     * Valida valor monetário
     */
    public static function validateMoney($value) {
        return is_numeric($value) && $value >= 0;
    }
}
?>