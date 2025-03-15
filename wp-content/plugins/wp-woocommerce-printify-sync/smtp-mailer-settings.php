// Use WordPress core functions for secure storage
update_option('smtp_mailer_settings', array(
    'smtp_host' => $encrypted_host,
    'smtp_port' => $encrypted_port,
    'smtp_username' => $encrypted_username,
    'smtp_password' => $encrypted_password,
    'smtp_secure' => 'tls', // or 'ssl'
    'smtp_auth' => true
), 'no'); // 'no' means do not autoload