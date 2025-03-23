<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Support;

class AttachmentHandler {
    private $upload_dir;
    private $allowed_types = [
        'image/jpeg', 'image/png', 'image/gif', 
        'application/pdf', 'text/plain',
        'application/msword', 
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->upload_dir = $upload_dir['basedir'] . '/wpwps-attachments';
        
        // Create secure attachments directory with .htaccess protection
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
            file_put_contents($this->upload_dir . '/.htaccess', 'deny from all');
            file_put_contents($this->upload_dir . '/index.php', '<?php // Silence is golden');
        }
    }

    public function saveAttachment(string $ticket_id, array $file): array {
        // Generate secure filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $secure_filename = wp_generate_password(32, false) . '.' . $ext;
        
        // Create ticket directory
        $ticket_dir = $this->upload_dir . '/' . $ticket_id;
        if (!file_exists($ticket_dir)) {
            wp_mkdir_p($ticket_dir);
        }

        $filepath = $ticket_dir . '/' . $secure_filename;
        
        // Move file to secure location
        move_uploaded_file($file['tmp_name'], $filepath);
        
        return [
            'id' => uniqid('att_'),
            'original_name' => $file['name'],
            'secure_name' => $secure_filename,
            'mime_type' => $file['type'],
            'size' => filesize($filepath),
            'path' => $filepath
        ];
    }

    public function getAttachment(string $ticket_id, string $filename): ?string {
        $filepath = $this->upload_dir . '/' . $ticket_id . '/' . $filename;
        
        if (file_exists($filepath) && $this->isPathSecure($filepath)) {
            return $filepath;
        }
        
        return null;
    }

    private function isPathSecure(string $filepath): bool {
        $real_path = realpath($filepath);
        return strpos($real_path, $this->upload_dir) === 0;
    }
}
