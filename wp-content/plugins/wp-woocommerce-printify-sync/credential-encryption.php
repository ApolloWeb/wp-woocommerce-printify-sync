function encrypt_credentials($value) {
    if (empty($value)) return '';
    
    // Use OpenSSL encryption with salting
    $encryption_key = wp_salt('auth');
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    
    $encrypted = openssl_encrypt(
        $value,
        'aes-256-cbc',
        $encryption_key,
        0,
        $iv
    );
    
    return base64_encode($iv . $encrypted);
}