// Use WordPress options API with encryption
update_option('postie_settings', array(
    'pop3_host' => $encrypted_host,
    'pop3_port' => $encrypted_port,
    'pop3_username' => $encrypted_username,
    'pop3_password' => $encrypted_password,
    'pop3_secure' => 'ssl', // or 'tls'
    'check_interval' => 300 // 5 minutes
), 'no');