<?php
// Clase para encriptar y desencriptar texto de forma segura
class Crypto
{
    // Clave secreta para el proyecto (se recomienda cambiarla en producción)
    private static $secret_key = 'VetCare_Clave_Secreta_Segura_2026';
    // Método de cifrado AES de 256 bits
    private static $encrypt_method = "AES-256-CBC";

    public static function encrypt($string)
    {
        // Genera una clave única basada en la frase secreta
        $key = hash('sha256', self::$secret_key);
        // Genera un vector de inicialización (IV) para mayor seguridad
        $iv = substr(hash('sha256', 'secret_iv'), 0, 16);

        // Realiza el cifrado
        $output = openssl_encrypt($string, self::$encrypt_method, $key, 0, $iv);
        // Retorna el texto cifrado convertido a Base64
        return base64_encode($output);
    }

    public static function decrypt($string)
    {
        // Usa la misma clave y el mismo IV que el método de encriptar
        $key = hash('sha256', self::$secret_key);
        $iv = substr(hash('sha256', 'secret_iv'), 0, 16);

        // Primero decodifica el Base64 y luego aplica la desencriptación
        $output = openssl_decrypt(base64_decode($string), self::$encrypt_method, $key, 0, $iv);
        return $output;
    }
}
