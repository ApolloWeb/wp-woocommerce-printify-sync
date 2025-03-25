namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class EncryptionHelper
{
    private static $encryption_key = 'your-secret-encryption-key';

    public static function encrypt($data)
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', self::$encryption_key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    public static function decrypt($data)
    {
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', self::$encryption_key, 0, $iv);
    }
}
